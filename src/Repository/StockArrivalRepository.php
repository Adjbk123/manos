<?php

namespace App\Repository;

use App\Entity\StockArrival;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockArrival>
 *
 * @method StockArrival|null find($id, $lockMode = null, $lockVersion = null)
 * @method StockArrival|null findOneBy(array $criteria, array $orderBy = null)
 * @method StockArrival[]    findAll()
 * @method StockArrival[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockArrivalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockArrival::class);
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
}
