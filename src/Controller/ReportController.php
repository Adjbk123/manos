<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\BalanceMovement;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use App\Repository\BalanceMovementRepository;
use App\Repository\TransactionRepository;
use App\Service\PdfService;
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
        BalanceMovementRepository $movementRepository,
        TransactionRepository $transactionRepository
    ): JsonResponse {
        $dateStr = $request->query->get('date', date('Y-m-d'));
        $data = $this->getDailyReportData($dateStr, $accountRepository, $movementRepository);

        return $this->json($data);
    }

    #[Route('/daily/pdf', name: 'api_report_daily_pdf', methods: ['GET'])]
    public function dailyPdf(
        Request $request,
        AccountRepository $accountRepository,
        BalanceMovementRepository $movementRepository,
        PdfService $pdfService
    ): \Symfony\Component\HttpFoundation\Response {
        $dateStr = $request->query->get('date', date('Y-m-d'));
        $data = $this->getDailyReportData($dateStr, $accountRepository, $movementRepository);

        // Convert logo to base64
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo-manos-phone.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdfBinary = $pdfService->generatePdf('reports/daily_pdf.html.twig', [
            'reportData' => $data['reportData'],
            'date' => $dateStr,
            'logo_path' => $logoBase64
        ]);

        return new \Symfony\Component\HttpFoundation\Response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rapport_journalier_' . $dateStr . '.pdf"',
        ]);
    }

    private function getDailyReportData(
        string $dateStr,
        AccountRepository $accountRepository,
        BalanceMovementRepository $movementRepository
    ): array {
        $startDate = new \DateTime($dateStr . ' 00:00:00');
        $endDate = new \DateTime($dateStr . ' 23:59:59');

        $accounts = $accountRepository->findAll();
        $reportData = [];

        foreach ($accounts as $account) {
            $movements = $movementRepository->createQueryBuilder('m')
                ->where('m.account = :account')
                ->andWhere('m.createdAt BETWEEN :start AND :end')
                ->setParameter('account', $account)
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->orderBy('m.createdAt', 'ASC')
                ->getQuery()->getResult();

            $totalDepots = 0;
            $totalRetraits = 0;
            $totalVentes = 0;

            foreach ($movements as $m) {
                $t = $m->getTransaction();
                if ($t) {
                    $opName = strtolower($t->getOperationType()->getName());
                    $catName = $t->getOperationType()->getCategory();
                    $amount = (float) $m->getAmount();

                    if (stripos($opName, 'dépôt') !== false || stripos($opName, 'depot') !== false) {
                        $totalDepots += $amount;
                    } elseif (stripos($opName, 'retrait') !== false) {
                        $totalRetraits += $amount;
                    } elseif (stripos($catName, 'Crédit') !== false || stripos($catName, 'Forfait') !== false) {
                        $totalVentes += $amount;
                    }
                }
            }

            if (empty($movements)) {
                $opening = (float) $account->getBalance();
                $closing = (float) $account->getBalance();
            } else {
                $opening = (float) $movements[0]->getBeforeBalance();
                $closing = (float) end($movements)->getAfterBalance();
            }

            $reportData[] = [
                'accountId' => $account->getId(),
                'accountName' => $account->getOperator() ? $account->getOperator()->getName() : 'Caisse Physique',
                'accountType' => $account->getType(),
                'accountLogo' => $account->getOperator() ? $account->getOperator()->getLogo() : null,
                'openingBalance' => (string) $opening,
                'closingBalance' => (string) $closing,
                'totalDepots' => $totalDepots,
                'totalRetraits' => $totalRetraits,
                'totalVentes' => $totalVentes,
                'ecart' => 0,
                'numMovements' => count($movements)
            ];
        }

        return [
            'reportData' => $reportData,
            'sessions' => [],
            'billetage' => [
                'theoretical' => 0,
                'physical' => 0,
                'ecart' => 0,
                'details' => []
            ],
            'date' => $dateStr
        ];
    }
}
