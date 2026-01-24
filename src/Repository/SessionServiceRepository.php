<?php

namespace App\Repository;

use App\Entity\SessionService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SessionService>
 */
class SessionServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionService::class);
    }

    public function findActiveSessionByUser($user): ?SessionService
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.agent = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SessionService::STATUS_OPEN)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAnyActiveSession(): ?SessionService
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', SessionService::STATUS_OPEN)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
