<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\BalanceMovement;
use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\OperationType;
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
        // Match category using constant from OperationType or loose match
        $isMM = ($category === OperationType::CATEGORY_MOBILE_MONEY) || (stripos($category, 'Mobile Money') !== false);
        
        if ($isMM) {
            if (!$virtualUV) {
                throw new \RuntimeException("Compte virtuel UV manquant pour cet opérateur.");
            }
            
            $v = strtolower($variant ?? '');
            if (stripos($v, 'retrait') !== false) {
                // RETRAIT pour le client = AGENT DONNE DU CASH / REÇOIT DE L'UV
                // Check Physical (Caisse)
                if (bccomp($physicalAccount->getBalance(), $amount, 2) === -1) {
                    throw new \RuntimeException("Solde PHYSIQUE (Caisse) insuffisant pour ce retrait.");
                }
                $this->adjust($virtualUV, $amount, $user, $t);
                $this->adjust($physicalAccount, "-$amount", $user, $t);
            } else if (stripos($v, 'dépôt') !== false) {
                // DÉPÔT pour le client = AGENT ENVOIE DE L'UV / REÇOIT DU CASH
                // Check Virtual (UV)
                if (bccomp($virtualUV->getBalance(), $amount, 2) === -1) {
                    throw new \RuntimeException("Solde VIRTUEL (UV) insuffisant pour ce dépôt.");
                }
                $this->adjust($virtualUV, "-$amount", $user, $t);
                $this->adjust($physicalAccount, $amount, $user, $t);
            }
        } else if (stripos($category, 'Crédit') !== false || stripos($category, 'Forfait') !== false) {
            // VENTE CRÉDIT/DATA = AGENT DÉDUIT STOCK / REÇOIT DU CASH
            $targetVirtual = $virtualCredit ?? $virtualUV;
            if (!$targetVirtual) {
                throw new \RuntimeException("Compte de crédit/UV manquant.");
            }

            if (bccomp($targetVirtual->getBalance(), $amount, 2) === -1) {
                throw new \RuntimeException("Solde de CRÉDIT insuffisant pour cette vente.");
            }

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
        
        // ULTIMATE PROTECTION: Prevent ANY negative balance
        if (bccomp($new, '0', 2) === -1) {
            $accountType = $account->getType() === Account::TYPE_PHYSICAL ? 'PHYSIQUE (Caisse)' : 'VIRTUEL';
            throw new \RuntimeException("Opération impossible : Solde $accountType insuffisant ($before F).");
        }

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
