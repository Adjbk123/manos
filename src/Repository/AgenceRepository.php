<?php

namespace App\Repository;

use App\Entity\Agence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Agence>
 */
class AgenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agence::class);
    }

    public function findOrCreateMainAgence(): Agence
    {
        $agence = $this->findOneBy([]);
        if (!$agence) {
            $agence = new Agence();
            $agence->setNom("MANO'S PHONE");
            $agence->setAdresse("Bénin, Cotonou");
            $agence->setContact("+229 00 00 00 00");
            
            $this->getEntityManager()->persist($agence);
            $this->getEntityManager()->flush();
        }
        return $agence;
    }
}
