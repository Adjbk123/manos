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
}
