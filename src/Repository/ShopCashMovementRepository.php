<?php

namespace App\Repository;

use App\Entity\ShopCashMovement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopCashMovement>
 */
class ShopCashMovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopCashMovement::class);
    }

    public function getBalance(): float
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT 
                SUM(CASE WHEN type = "IN" THEN amount ELSE -amount END) as balance
            FROM shop_cash_movement
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        
        return (float) $result->fetchOne() ?: 0.0;
    }
}
