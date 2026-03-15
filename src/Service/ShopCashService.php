<?php

namespace App\Service;

use App\Entity\ShopCashMovement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ShopCashService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function addMovement(
        string $type, // IN, OUT
        float $amount,
        string $label,
        string $source, // SALE, SUPPLIER_PAYMENT, EXPENSE, MANUAL
        ?int $sourceId = null,
        ?User $user = null
    ): ShopCashMovement {
        $movement = new ShopCashMovement();
        $movement->setType($type);
        $movement->setAmount((string)$amount);
        $movement->setLabel($label);
        $movement->setSource($source);
        $movement->setSourceId($sourceId);
        $movement->setCreatedBy($user);

        $this->em->persist($movement);
        
        return $movement;
    }
}
