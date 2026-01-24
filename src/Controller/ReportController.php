<?php

namespace App\Controller;

use App\Repository\AccountRepository;
use App\Repository\RapportSessionRepository;
use App\Repository\SessionServiceRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/reports')]
class ReportController extends AbstractController
{
    #[Route('/daily', name: 'api_report_daily', methods: ['GET'])]
    public function daily(
        Request $request,
        AccountRepository $accountRepository,
        RapportSessionRepository $rapportRepository,
        SessionServiceRepository $sessionRepository
    ): JsonResponse {
        $dateStr = $request->query->get('date', date('Y-m-d'));
        $startDate = new \DateTime($dateStr . ' 00:00:00');
        $endDate = new \DateTime($dateStr . ' 23:59:59');

        // Trouver toutes les sessions terminées ce jour
        $sessions = $sessionRepository->createQueryBuilder('s')
            ->where('s.startedAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()->getResult();

        $accounts = $accountRepository->findAll();
        $reportData = [];

        foreach ($accounts as $account) {
            // Pour chaque compte, on agrège les données des sessions du jour
            $rapports = $rapportRepository->createQueryBuilder('r')
                ->join('r.session', 's')
                ->where('r.compte = :account')
                ->andWhere('s.startedAt BETWEEN :start AND :end')
                ->setParameter('account', $account)
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->orderBy('s.startedAt', 'ASC')
                ->getQuery()->getResult();

            if (empty($rapports)) {
                continue;
            }

            $firstRapport = $rapports[0];
            $lastRapport = end($rapports);

            $totalDepots = 0;
            $totalRetraits = 0;
            $totalVentes = 0;
            $totalEcart = 0;

            foreach ($rapports as $r) {
                $totalDepots += (float) $r->getTotalDepots();
                $totalRetraits += (float) $r->getTotalRetraits();
                $totalVentes += (float) $r->getTotalVentes();
                $totalEcart += (float) $r->getEcart();
            }

            $reportData[] = [
                'accountId' => $account->getId(),
                'accountName' => $account->getOperator() ? $account->getOperator()->getName() : 'Caisse Physique',
                'accountType' => $account->getType(),
                'accountLogo' => $account->getOperator() ? $account->getOperator()->getLogo() : null,
                'openingBalance' => $firstRapport->getSoldeOuverture(),
                'closingBalance' => $lastRapport->getSoldeConfirmeFermeture(),
                'totalDepots' => $totalDepots,
                'totalRetraits' => $totalRetraits,
                'totalVentes' => $totalVentes,
                'ecart' => $totalEcart,
                'numSessions' => count($rapports)
            ];
        }

        return $this->json($reportData);
    }
}
