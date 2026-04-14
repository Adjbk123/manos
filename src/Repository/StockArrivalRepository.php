<?php
/**
 * Arrivage de stock repository
 */
namespace App\Repository;

use App\Entity\StockArrival;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockArrival>
 */
class StockArrivalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockArrival::class);
    }

    public function generateReference(): string
    {
        $date = new \DateTime();
        $prefix = 'ARR-' . $date->format('Ymd');
        
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.reference LIKE :prefix')
            ->setParameter('prefix', $prefix . '-%')
            ->getQuery()
            ->getSingleScalarResult();
            
        return sprintf('%s-%03d', $prefix, $count + 1);
    }

    public function save(StockArrival $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StockArrival $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.supplier', 's')
            ->addSelect('s')
            ->orderBy('a.arrivalDate', 'DESC');

        if (!empty($filters['search'])) {
            $qb->andWhere('a.reference LIKE :search OR s.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['start'])) {
            $qb->andWhere('a.arrivalDate >= :start')
               ->setParameter('start', new \DateTime($filters['start'] . ' 00:00:00'));
        }

        if (!empty($filters['end'])) {
            $qb->andWhere('a.arrivalDate <= :end')
               ->setParameter('end', new \DateTime($filters['end'] . ' 23:59:59'));
        }

        return $qb->getQuery()->getResult();
    }
}
