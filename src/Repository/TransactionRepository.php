<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function getPerformanceStats(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('t')
            ->select('SUBSTRING(t.createdAt, 1, 10) as date', 'o.name as operator', 'ot.name as service', 'ot.category', 'SUM(t.amount) as volume')
            ->join('t.operationType', 'ot')
            ->join('t.operator', 'o')
            ->where('t.createdAt BETWEEN :start AND :end')
            ->andWhere('t.status = :status')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', Transaction::STATUS_SUCCESS)
            ->groupBy('date', 'o.name', 'ot.name', 'ot.category')
            ->orderBy('date', 'ASC')
            ->getQuery()->getResult();
    }

    public function getServiceDistributionStats(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('t')
            ->select('ot.name as service', 'ot.category', 'o.name as operator', 'SUM(t.amount) as volume', 'COUNT(t.id) as count')
            ->join('t.operationType', 'ot')
            ->join('t.operator', 'o')
            ->where('t.createdAt BETWEEN :start AND :end')
            ->andWhere('t.status = :status')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', Transaction::STATUS_SUCCESS)
            ->groupBy('ot.name', 'ot.category', 'o.name')
            ->getQuery()->getResult();
    }

    public function getRecentTransactions(int $limit = 10)
    {
        return $this->createQueryBuilder('t')
            ->join('t.operationType', 'ot')
            ->join('t.operator', 'o')
            ->leftJoin('t.customer', 'c')
            ->select('t.id', 't.amount', 't.createdAt', 't.status', 'ot.name as type', 'o.name as operator', 'o.logo as operator_logo', 'c.id as customer_id', 'c.phone as customer_phone', 'c.nom', 'c.prenom')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    public function getGlobalVolumeStats(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('t')
            ->select('ot.category', 'ot.name as variant', 'SUM(t.amount) as volume')
            ->join('t.operationType', 'ot')
            ->where('t.createdAt BETWEEN :start AND :end')
            ->andWhere('t.status = :status')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', Transaction::STATUS_SUCCESS)
            ->groupBy('ot.category', 'ot.name')
            ->getQuery()->getResult();
    }

    public function getSessionStatsByAccount($session, $account)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as volume', 'ot.category', 'ot.name')
            ->join('t.operationType', 'ot')
            ->where('t.sessionService = :session')
            ->andWhere('t.status = :status')
            ->setParameter('session', $session)
            ->setParameter('status', Transaction::STATUS_SUCCESS);

        if ($account->getOperator()) {
            $qb->andWhere('t.operator = :operator')
               ->setParameter('operator', $account->getOperator());
        }

        $results = $qb->groupBy('ot.category', 'ot.name')->getQuery()->getResult();
        
        $stats = ['deposits' => 0, 'withdrawals' => 0, 'sales' => 0];
        foreach ($results as $res) {
            $cat = $res['category'];
            $name = strtolower($res['name']);
            $volume = (float) $res['volume'];
            
            if ($cat === 'MOBILE_MONEY') {
                if (str_contains($name, 'dépôt') || str_contains($name, 'depot')) {
                    $stats['deposits'] += $volume;
                } elseif (str_contains($name, 'retrait')) {
                    $stats['withdrawals'] += $volume;
                }
            } elseif ($cat === 'CREDIT_FORFAIT') {
                $stats['sales'] += $volume;
            }
        }
        return $stats;
    }

    public function getSessionStatsByOperator($session, $operator)
    {
        $results = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as volume', 'COUNT(t.id) as count', 'ot.category', 'ot.name')
            ->join('t.operationType', 'ot')
            ->where('t.sessionService = :session')
            ->andWhere('t.operator = :operator')
            ->andWhere('t.status = :status')
            ->setParameter('session', $session)
            ->setParameter('operator', $operator)
            ->setParameter('status', Transaction::STATUS_SUCCESS)
            ->groupBy('ot.category', 'ot.name')
            ->getQuery()->getResult();

        $stats = [
            'DEPOT' => ['volume' => 0, 'count' => 0],
            'RETRAIT' => ['volume' => 0, 'count' => 0],
            'VENTE' => ['volume' => 0, 'count' => 0],
        ];

        foreach ($results as $res) {
            $cat = $res['category'];
            $name = strtolower($res['name']);
            $vol = (float) $res['volume'];
            $cnt = (int) $res['count'];

            if ($cat === 'MOBILE_MONEY') {
                if (str_contains($name, 'dépôt') || str_contains($name, 'depot')) {
                    $stats['DEPOT']['volume'] += $vol;
                    $stats['DEPOT']['count'] += $cnt;
                } elseif (str_contains($name, 'retrait')) {
                    $stats['RETRAIT']['volume'] += $vol;
                    $stats['RETRAIT']['count'] += $cnt;
                }
            } elseif ($cat === 'CREDIT_FORFAIT') {
                $stats['VENTE']['volume'] += $vol;
                $stats['VENTE']['count'] += $cnt;
            }
        }
        return $stats;
    }
}
