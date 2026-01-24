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
use App\Service\DashboardStatsService;

#[Route('/api/stats')]
class StatsController extends AbstractController
{
    #[Route('/dashboard', name: 'api_stats_dashboard', methods: ['GET'])]
    public function dashboard(
        \Symfony\Component\HttpFoundation\Request $request,
        DashboardStatsService $statsService
    ): JsonResponse {
        // Date Range for Performance/History
        $startDate = $request->query->get('start') ? new \DateTime($request->query->get('start')) : new \DateTime('first day of this month 00:00:00');
        $endDate = $request->query->get('end') ? new \DateTime($request->query->get('end')) : new \DateTime('now');
        $endDate->setTime(23, 59, 59);

        $data = $statsService->getDashboardStats($startDate, $endDate);

        return $this->json($data);
    }
}
