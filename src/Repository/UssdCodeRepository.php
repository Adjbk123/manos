<?php

namespace App\Repository;

use App\Entity\UssdCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UssdCode>
 *
 * @method UssdCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method UssdCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method UssdCode[]    findAll()
 * @method UssdCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UssdCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UssdCode::class);
    }
}
