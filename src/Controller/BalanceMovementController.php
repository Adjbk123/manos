<?php

namespace App\Controller;

use App\Repository\BalanceMovementRepository;
use App\Entity\Operator;
use App\Entity\User;
use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/balance-movements')]
class BalanceMovementController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('', name: 'app_balance_movement_index', methods: ['GET'])]
    public function index(Request $request, BalanceMovementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $qb = $repo->createQueryBuilder('m')
            ->join('m.account', 'a')
            ->orderBy('m.createdAt', 'DESC');

        if ($operatorId = $request->query->get('operator_id')) {
            $qb->andWhere('a.operator = :operatorId')
               ->setParameter('operatorId', $operatorId);
        }

        if ($userId = $request->query->get('user_id')) {
            $qb->andWhere('m.user = :userId')
               ->setParameter('userId', $userId);
        }

        if ($accountType = $request->query->get('account_type')) {
            // Mapping frontend terminology to backend if needed
            $type = ($accountType === 'physique') ? 'physical' : (($accountType === 'virtuel') ? 'virtual' : $accountType);
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        if ($accountId = $request->query->get('account_id')) {
            $qb->andWhere('a.id = :accountId')
               ->setParameter('accountId', $accountId);
        }

        if ($startDate = $request->query->get('start_date')) {
            // client sends 'YYYY-MM-DD'
             try {
                $start = new \DateTime($startDate);
                $start->setTime(0, 0, 0);
                $qb->andWhere('m.createdAt >= :start')
                   ->setParameter('start', $start);
            } catch (\Exception $e) {}
        }

        if ($endDate = $request->query->get('end_date')) {
             try {
                $end = new \DateTime($endDate);
                $end->setTime(23, 59, 59);
                $qb->andWhere('m.createdAt <= :end')
                   ->setParameter('end', $end);
            } catch (\Exception $e) {}
        }

        $movements = $qb->setMaxResults(100)->getQuery()->getResult();

        $json = $this->serializer->serialize($movements, 'json', ['groups' => 'balance_movement:read']);
        return new JsonResponse($json, 200, [], true);
    }
}
