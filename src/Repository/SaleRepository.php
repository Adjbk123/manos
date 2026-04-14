<?php

namespace App\Repository;

use App\Entity\Sale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sale>
 */
class SaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sale::class);
    }

    public function getStats(\DateTime $start, \DateTime $end): array
    {
        $volume = $this->createQueryBuilder('s')
            ->select('SUM(s.totalAmount)')
            ->where('s.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        $profit = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(si.profit)')
            ->from(\App\Entity\SaleItem::class, 'si')
            ->join('si.sale', 's')
            ->where('s.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_sales' => (float)($volume ?? 0),
            'total_profit' => (float)($profit ?? 0)
        ];
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.stockClient', 'c')
            ->addSelect('c')
            ->orderBy('s.date', 'DESC');

        if (!empty($filters['search'])) {
            $qb->andWhere('s.reference LIKE :search OR c.name LIKE :search OR s.clientName LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['start'])) {
            $qb->andWhere('s.date >= :start')
               ->setParameter('start', new \DateTime($filters['start'] . ' 00:00:00'));
        }

        if (!empty($filters['end'])) {
            $qb->andWhere('s.date <= :end')
               ->setParameter('end', new \DateTime($filters['end'] . ' 23:59:59'));
        }

        return $qb->getQuery()->getResult();
    }
}
