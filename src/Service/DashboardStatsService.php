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

    public function __construct(
        TransactionRepository $transactionRepository,
        AccountRepository $accountRepository,
        OperatorRepository $operatorRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->accountRepository = $accountRepository;
        $this->operatorRepository = $operatorRepository;
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
            $cat = $row['category'] ?? '';
            $svc = mb_strtolower($row['service']);
            
            if ($cat === OperationType::CATEGORY_MOBILE_MONEY) {
                if (str_contains($svc, 'dépôt') || str_contains($svc, 'depot')) $type = 'deposit';
                elseif (str_contains($svc, 'retrait')) $type = 'withdrawal';
            } elseif ($cat === OperationType::CATEGORY_CREDIT_DATA) {
                $type = 'sale';
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
            $cat = $stat['category'] ?? '';
            $var = mb_strtolower($stat['variant'] ?? '');
            $vol = (float)$stat['volume'];

            if ($cat === OperationType::CATEGORY_MOBILE_MONEY) {
                if (str_contains($var, 'dépôt') || str_contains($var, 'depot')) $totalDepots += $vol;
                elseif (str_contains($var, 'retrait')) $totalRetraits += $vol;
            } 
            
            if ($cat === OperationType::CATEGORY_CREDIT_DATA) {
                if (str_contains($var, 'forfait')) {
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
                ]
            ],
            'performance' => $performanceData,
            'distribution' => $distribution,
            'recent_transactions' => $recentClean,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ]
        ];
    }
}
