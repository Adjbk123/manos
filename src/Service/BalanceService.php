<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\BalanceMovement;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;

class BalanceService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Reconciles balances for a successful transaction based on Benin-style Mobile Money logic.
     */
    public function triggerTransactionMovements(Transaction $t): void
    {
        $opType = $t->getOperationType();
        $category = $opType->getCategory();
        $variant = $opType->getVariant();
        $amount = $t->getAmount();
        $operator = $opType->getOperator();
        $user = $t->getUser();

        // 1. Get Accounts
        $virtualUV = $this->em->getRepository(Account::class)->findOneBy([
            'operator' => $operator,
            'type' => Account::TYPE_VIRTUAL
        ]);

        $virtualCredit = $this->em->getRepository(Account::class)->findOneBy([
            'operator' => $operator,
            'type' => Account::TYPE_VIRTUAL_CREDIT
        ]);

        $physicalAccount = $this->em->getRepository(Account::class)->findOneBy([
            'operator' => null,
            'type' => Account::TYPE_PHYSICAL
        ]);

        if (!$physicalAccount) {
            $physicalAccount = new Account();
            $physicalAccount->setType(Account::TYPE_PHYSICAL);
            $physicalAccount->setBalance('0');
            $this->em->persist($physicalAccount);
        }

        // 2. Apply rules
        if ($category === 'Mobile Money') {
            if (!$virtualUV) return; // Silent skip if account missing
            
            if (stripos($variant, 'retrait') !== false) {
                // Retrait: -Virtuel, -Physique
                $this->adjust($virtualUV, "-$amount", $user, $t);
                $this->adjust($physicalAccount, "-$amount", $user, $t);
            } else if (stripos($variant, 'dépôt') !== false) {
                // Dépôt: +Virtuel, +Physique
                $this->adjust($virtualUV, $amount, $user, $t);
                $this->adjust($physicalAccount, $amount, $user, $t);
            }
        } else if ($category === 'Vente Crédit' || $category === 'Vente Forfait') {
            // Vente: -Virtuel, +Physique
            // Priority to virtual_credit if it exists, otherwise use standard virtual
            $targetVirtual = $virtualCredit ?? $virtualUV;
            if (!$targetVirtual) return;

            $this->adjust($targetVirtual, "-$amount", $user, $t);
            $this->adjust($physicalAccount, $amount, $user, $t);
        }
        
        $this->em->flush();
    }

    /**
     * Core method to adjust an account balance.
     */
    public function adjust(Account $account, string $amount, User $user, ?Transaction $t = null, ?string $notes = null): void
    {
        $before = $account->getBalance();
        $new = bcadd($before, $amount, 2);
        
        $account->setBalance($new);

        $movement = new BalanceMovement();
        $movement->setAccount($account);
        $movement->setAmount(str_replace('-', '', $amount));
        $movement->setBeforeBalance($before);
        $movement->setAfterBalance($new);
        $movement->setUser($user);
        $movement->setType($t ? BalanceMovement::TYPE_TRANSACTION : BalanceMovement::TYPE_ADJUST);
        $movement->setTransaction($t);
        $movement->setNotes($notes);
        
        $this->em->persist($movement);
    }
}
