<?php

namespace App\Service;

use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Repository\OperatorRepository;
use App\Repository\TransactionRepository;
use App\Entity\OperationType;

class DashboardStatsService
{
    private TransactionRepository $transactionRepository;
    private AccountRepository $accountRepository;
    private OperatorRepository $operatorRepository;
    private \App\Repository\SaleRepository $saleRepository;
    private \App\Repository\SupplierRepository $supplierRepository;
    private \App\Repository\ShopCashMovementRepository $shopCashRepository;
    private \App\Repository\StockAdjustmentRepository $adjustmentRepository;
    private \App\Repository\StockClientRepository $clientRepository;

    public function __construct(
        TransactionRepository $transactionRepository,
        AccountRepository $accountRepository,
        OperatorRepository $operatorRepository,
        \App\Repository\SaleRepository $saleRepository,
        \App\Repository\SupplierRepository $supplierRepository,
        \App\Repository\ShopCashMovementRepository $shopCashRepository,
        \App\Repository\StockAdjustmentRepository $adjustmentRepository,
        \App\Repository\StockClientRepository $clientRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->accountRepository = $accountRepository;
        $this->operatorRepository = $operatorRepository;
        $this->saleRepository = $saleRepository;
        $this->supplierRepository = $supplierRepository;
        $this->shopCashRepository = $shopCashRepository;
        $this->adjustmentRepository = $adjustmentRepository;
        $this->clientRepository = $clientRepository;
    }

    public function getDashboardStats(\DateTime $startDate, \DateTime $endDate): array
    {
        // 1. Snapshot Balances (KPIs) - Current State
        $physicalAccount = $this->accountRepository->findPhysicalAccount();
        $totalCash = $physicalAccount ? (float)$physicalAccount->getBalance() : 0;
        
        $operators = $this->operatorRepository->findAll();
        $networkStats = [];
        $totalUV = 0;

        foreach ($operators as $op) {
            $accounts = $this->accountRepository->findBy(['operator' => $op]);
            $opAccounts = [];
            
            foreach ($accounts as $acc) {
                if ($acc->getType() !== Account::TYPE_PHYSICAL) {
                    $balance = (float)$acc->getBalance();
                    $opAccounts[] = [
                        'type' => $acc->getType(),
                        'balance' => $balance,
                        'currency' => $acc->getCurrency()
                    ];
                    if ($acc->getType() === Account::TYPE_VIRTUAL) {
                        $totalUV += $balance;
                    }
                }
            }

            $networkStats[] = [
                'id' => $op->getId(),
                'name' => $op->getName(),
                'logo' => $op->getLogo(),
                'accounts' => $opAccounts
            ];
        }

        // 2. Performance Chart Data (Period)
        $performanceData = [];
        
        $transactions = $this->transactionRepository->getPerformanceStats($startDate, $endDate);

        foreach ($transactions as $row) {
            $type = 'other';
            $code = $row['code'] ?? '';
            
            if ($code === OperationType::CODE_DEPOSIT) $type = 'deposit';
            elseif ($code === OperationType::CODE_WITHDRAWAL) $type = 'withdrawal';
            elseif ($code === OperationType::CODE_CREDIT) $type = 'sale';
            else {
                // FALLBACK if code is not set
                $cat = $row['category'] ?? '';
                $svc = mb_strtolower($row['service'] ?? '');
                
                if ($cat === OperationType::CATEGORY_MOBILE_MONEY) {
                    if (str_contains($svc, 'dépôt') || str_contains($svc, 'depot')) $type = 'deposit';
                    elseif (str_contains($svc, 'retrait')) $type = 'withdrawal';
                } elseif ($cat === OperationType::CATEGORY_CREDIT_DATA) {
                    $type = 'sale';
                }
            }
            
            if ($type !== 'other') {
                $performanceData[] = [
                    'date' => $row['date'],
                    'operator' => $row['operator'],
                    'type' => $type,
                    'volume' => (float)$row['volume']
                ];
            }
        }

        // 3. Service Distribution (Period)
        $distribution = $this->transactionRepository->getServiceDistributionStats($startDate, $endDate);

        // 4. Recent Transactions (Last 10)
        $recent = $this->transactionRepository->getRecentTransactions(10);
            
        $recentClean = array_map(function($row) {
            return [
                'id' => $row['id'],
                'amount' => $row['amount'],
                'date' => $row['createdAt']->format('c'),
                'status' => $row['status'],
                'type' => $row['type'],
                'operator' => $row['operator'],
                'operator_logo' => $row['operator_logo'],
                'customer' => [
                    'id' => $row['customer_id'],
                    'phone' => $row['customer_phone'],
                    'name' => trim(($row['nom'] ?? '') . ' ' . ($row['prenom'] ?? ''))
                ]
            ];
        }, $recent);

        // 5. Global Totals (KPIs)
        $globalStats = $this->transactionRepository->getGlobalVolumeStats($startDate, $endDate);

        $totalDepots = 0;
        $totalRetraits = 0;
        $totalCredit = 0;
        $totalForfait = 0;

        foreach ($globalStats as $stat) {
            $code = $stat['code'] ?? '';
            $vol = (float)$stat['volume'];
            $type = 'other';

            if ($code === OperationType::CODE_DEPOSIT) $type = 'deposit';
            elseif ($code === OperationType::CODE_WITHDRAWAL) $type = 'withdrawal';
            elseif ($code === OperationType::CODE_CREDIT) $type = 'sale';
            else {
                $cat = $stat['category'] ?? '';
                $svc = mb_strtolower($stat['variant'] ?? '');
                
                if ($cat === OperationType::CATEGORY_MOBILE_MONEY) {
                    if (str_contains($svc, 'dépôt') || str_contains($svc, 'depot')) $type = 'deposit';
                    elseif (str_contains($svc, 'retrait')) $type = 'withdrawal';
                } elseif ($cat === OperationType::CATEGORY_CREDIT_DATA) {
                    $type = 'sale';
                }
            }

            if ($type === 'deposit') {
                $totalDepots += $vol;
            } elseif ($type === 'withdrawal') {
                $totalRetraits += $vol;
            } elseif ($type === 'sale') {
                if (str_contains(mb_strtolower($stat['variant'] ?? ''), 'forfait')) {
                    $totalForfait += $vol;
                } else {
                    $totalCredit += $vol;
                }
            }
        }

        return [
            'kpi' => [
                'total_cash' => $totalCash,
                'total_uv' => $totalUV,
                'networks' => $networkStats,
                'volumes' => [
                    'depots' => $totalDepots,
                    'retraits' => $totalRetraits,
                    'credit' => $totalCredit,
                    'forfait' => $totalForfait
                ],
                'distribution' => [
                    ['service' => 'Dépôts', 'volume' => $totalDepots],
                    ['service' => 'Retraits', 'volume' => $totalRetraits],
                    ['service' => 'Ventes', 'volume' => $totalCredit + $totalForfait],
                ],
                'store' => array_merge(
                    $this->saleRepository->getStats($startDate, $endDate),
                    [
                        'shop_cash_balance' => $this->shopCashRepository->getBalance(),
                        'supplier_debt' => (float)$this->supplierRepository->createQueryBuilder('s')->select('SUM(s.balance)')->getQuery()->getSingleScalarResult(),
                        'client_debt' => (float)$this->clientRepository->createQueryBuilder('c')->select('SUM(c.currentDebt)')->getQuery()->getSingleScalarResult(),
                        'losses_value' => (float)$this->adjustmentRepository->createQueryBuilder('a')
                            ->join('a.batch', 'b')
                            ->select('SUM(a.quantity * b.purchasePrice)')
                            ->where('a.createdAt BETWEEN :start AND :end')
                            ->setParameter('start', $startDate)
                            ->setParameter('end', $endDate)
                            ->getQuery()
                            ->getSingleScalarResult()
                    ]
                )
            ],
            'performance' => $performanceData,
            'distribution' => $distribution,
            'recent_transactions' => $recentClean,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'recent' => $this->transactionRepository->getRecentTransactions(10)
        ];
    }
}
