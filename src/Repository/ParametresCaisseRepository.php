<?php

namespace App\Repository;

use App\Entity\ParametresCaisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParametresCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParametresCaisse::class);
    }

    public function findSettings(): ?ParametresCaisse
    {
        return $this->findOneBy([]);
    }
}
