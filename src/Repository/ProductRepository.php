<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->orderBy('p.name', 'ASC');

        if (!empty($filters['category_id'])) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $filters['category_id']);
        }

        if (!empty($filters['active_filter'])) {
            $isActive = $filters['active_filter'] === 'active';
            $qb->andWhere('p.isActive = :isActive')
               ->setParameter('isActive', $isActive);
        }

        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'IN_STOCK':
                    $qb->andWhere('p.stockQuantity > 0');
                    break;
                case 'LOW_STOCK':
                    $qb->andWhere('p.stockQuantity <= p.alertThreshold')
                       ->andWhere('p.stockQuantity > 0');
                    break;
                case 'OUT_OF_STOCK':
                    $qb->andWhere('p.stockQuantity <= 0');
                    break;
            }
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['min_price'])) {
            $qb->andWhere('p.sellingPrice >= :minPrice')
               ->setParameter('minPrice', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $qb->andWhere('p.sellingPrice <= :maxPrice')
               ->setParameter('maxPrice', $filters['max_price']);
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
