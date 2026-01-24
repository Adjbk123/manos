<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\BalanceMovement;
use App\Entity\Transaction;
use App\Entity\Loan;
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
        $amount = $t->getAmount();
        $operator = $t->getOperator();
        $user = $t->getUser();

        // 1. Get Accounts
        $virtualAccount = $this->em->getRepository(Account::class)->findOneBy([
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
            if (!$virtualAccount) {
                throw new \RuntimeException("Compte VIRTUEL manquant pour cet opérateur.");
            }

            $name = strtolower($opType->getName());
            if (stripos($name, 'retrait') !== false) {
                // RETRAIT pour le client = AGENT DONNE DU CASH / REÇOIT DU VIRTUEL
                // Check Physical (Caisse)
                if (bccomp($physicalAccount->getBalance(), $amount, 2) === -1) {
                    throw new \RuntimeException("Solde PHYSIQUE (Caisse) insuffisant pour ce retrait.");
                }
                $this->adjust($virtualAccount, $amount, $user, $t);
                $this->adjust($physicalAccount, "-$amount", $user, $t);
            } else if (stripos($name, 'dépôt') !== false || stripos($name, 'depot') !== false) {
                // DÉPÔT pour le client = AGENT ENVOIE DU VIRTUEL / REÇOIT DU CASH
                // Check Virtual
                if (bccomp($virtualAccount->getBalance(), $amount, 2) === -1) {
                    throw new \RuntimeException("Solde VIRTUEL insuffisant pour ce dépôt.");
                }
                $this->adjust($virtualAccount, "-$amount", $user, $t);
                $this->adjust($physicalAccount, $amount, $user, $t);
            }
        } else if (stripos($category, 'Crédit') !== false || stripos($category, 'Forfait') !== false) {
            // VENTE CRÉDIT/DATA = AGENT DÉDUIT STOCK / REÇOIT DU CASH
            $targetVirtual = $virtualCredit ?? $virtualAccount;
            if (!$targetVirtual) {
                throw new \RuntimeException("Compte de crédit/VIRTUEL manquant.");
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
     * Reverses the movements of a transaction if it is cancelled.
     */
    public function triggerTransactionReversal(Transaction $t): void
    {
        $movements = $this->em->getRepository(BalanceMovement::class)->findBy(['transaction' => $t]);

        foreach ($movements as $m) {
            $account = $m->getAccount();
            $amountToReverse = $m->getAmount();

            // If the movement was positive, we subtract it. If negative, we add it.
            $before = $m->getBeforeBalance();
            $after = $m->getAfterBalance();

            if (bccomp($after, $before, 2) === 1) {
                // It was an addition, so we subtract
                $adjustment = "-$amountToReverse";
            } else {
                // It was a subtraction, so we add
                $adjustment = $amountToReverse;
            }

            // For reversal, we bypass the negative balance check if needed? 
            // Better to keep it to ensure data integrity, but cancellation should theoretically be safe.
            $this->adjust($account, $adjustment, $t->getUser(), $t, "ANNULATION de la transaction #{$t->getId()}");
        }

        $this->em->flush();
    }

    /**
     * Core method to adjust an account balance.
     */
    public function adjust(Account $account, string $amount, User $user, ?Transaction $t = null, ?string $notes = null, string $type = BalanceMovement::TYPE_ADJUST, ?Loan $loan = null): void
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
        $movement->setType($t ? BalanceMovement::TYPE_TRANSACTION : $type);
        $movement->setTransaction($t);
        $movement->setLoan($loan);
        $movement->setNotes($notes);

        $this->em->persist($movement);
    }
}
