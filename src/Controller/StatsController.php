<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Repository\AccountRepository;
use App\Repository\OperatorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/stats')]
class StatsController extends AbstractController
{
    #[Route('/dashboard', name: 'api_stats_dashboard', methods: ['GET'])]
    public function dashboard(
        TransactionRepository $transactionRepository, 
        AccountRepository $accountRepository,
        OperatorRepository $operatorRepository
    ): JsonResponse {
        $today = new \DateTime('today');
        
        // 1. Finances (Benin Terrain Logic)
        $physicalAccount = $accountRepository->findPhysicalAccount();
        $totalCash = $physicalAccount ? (float)$physicalAccount->getBalance() : 0;
        
        $virtualAccounts = $accountRepository->findBy(['type' => [Account::TYPE_VIRTUAL, Account::TYPE_VIRTUAL_CREDIT]]);
        $totalUV = 0;
        $balanceStats = [];
        
        $operators = $operatorRepository->findAll();
        foreach ($operators as $op) {
            $uvAccs = $accountRepository->findBy(['operator' => $op]);
            $opUV = 0;
            foreach ($uvAccs as $acc) {
                if ($acc->getType() !== Account::TYPE_PHYSICAL) {
                    $opUV += (float)$acc->getBalance();
                }
            }
            $totalUV += $opUV;
            
            $balanceStats[] = [
                'name' => $op->getName(),
                'logo' => $op->getLogo(),
                'uv' => $opUV,
                'cash' => $totalCash
            ];
        }

        // 2. Volumes
        $dailyVolume = $transactionRepository->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.createdAt >= :today AND t.status = :status')
            ->setParameter('today', $today)
            ->setParameter('status', Transaction::STATUS_SUCCESS)
            ->getQuery()->getSingleScalarResult() ?? 0;

        // 3. MM Stats
        $mmStats = $transactionRepository->createQueryBuilder('t')
            ->select('ot.variant as variant, SUM(t.amount) as amount')
            ->join('t.operationType', 'ot')
            ->where('t.createdAt >= :today AND t.status = :status AND ot.category = :category')
            ->setParameter('today', $today)
            ->setParameter('status', Transaction::STATUS_SUCCESS)
            ->setParameter('category', 'Mobile Money')
            ->groupBy('ot.variant')
            ->getQuery()->getResult();

        $depots = 0; $retraits = 0;
        foreach ($mmStats as $stat) {
            if (stripos($stat['variant'], 'dépôt') !== false) $depots = (float)$stat['amount'];
            if (stripos($stat['variant'], 'retrait') !== false) $retraits = (float)$stat['amount'];
        }

        // 4. Ventes Stats
        $ventesByOp = $transactionRepository->createQueryBuilder('t')
            ->select('o.name as operator, SUM(t.amount) as amount')
            ->join('t.operationType', 'ot')
            ->join('ot.operator', 'o')
            ->where('t.createdAt >= :today AND t.status = :status AND (ot.category = :cat1 OR ot.category = :cat2)')
            ->setParameter('today', $today)
            ->setParameter('status', Transaction::STATUS_SUCCESS)
            ->setParameter('cat1', 'Vente Crédit')
            ->setParameter('cat2', 'Vente Forfait')
            ->groupBy('o.name')
            ->getQuery()->getResult();

        $totalVentes = 0;
        foreach ($ventesByOp as $v) $totalVentes += (float)$v['amount'];

        return $this->json([
            'financials' => [
                'totalCash' => $totalCash,
                'totalUV' => $totalUV,
                'volumes' => ['day' => $dailyVolume],
                'netFlow' => ($depots + $totalVentes) - $retraits,
                'mm' => ['depots' => $depots, 'retraits' => $retraits],
                'ventes' => ['total' => $totalVentes, 'byOperator' => $ventesByOp]
            ],
            'operational' => [
                'networks' => $balanceStats,
                'totalOps' => $transactionRepository->count(['status' => Transaction::STATUS_SUCCESS]), // Simplified
                'clients' => $transactionRepository->createQueryBuilder('t')
                    ->select('COUNT(DISTINCT t.customer)')
                    ->where('t.createdAt >= :today AND t.status = :status')
                    ->setParameter('today', $today)
                    ->setParameter('status', Transaction::STATUS_SUCCESS)
                    ->getQuery()->getSingleScalarResult(),
            ],
            'hourly' => $transactionRepository->createQueryBuilder('t')
                ->select('HOUR(t.createdAt) as hour, SUM(t.amount) as amount')
                ->where('t.createdAt >= :today AND t.status = :status')
                ->setParameter('today', $today)
                ->setParameter('status', Transaction::STATUS_SUCCESS)
                ->groupBy('hour')
                ->orderBy('hour', 'ASC')
                ->getQuery()
                ->getResult()
        ]);
    }
}
