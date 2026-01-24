<?php

namespace App\Service;

use App\Entity\Loan;
use App\Entity\User;
use App\Entity\Partner;
use App\Entity\Operator;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Account;
use App\Entity\BalanceMovement;

class LoanService
{
    private EntityManagerInterface $em;
    private BalanceService $balanceService;

    public function __construct(EntityManagerInterface $em, BalanceService $balanceService)
    {
        $this->em = $em;
        $this->balanceService = $balanceService;
    }

    /**
     * Records a new loan for an agent with an external partner.
     */
    public function recordLoan(User $user, Partner $partner, array $data): Loan
    {
        $amount = $data['amount'] ?? null;
        if (!$amount || !is_numeric($amount) || bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à zéro.');
        }

        $type = $data['type'] ?? Loan::TYPE_CASH;
        $direction = $data['direction'] ?? Loan::DIRECTION_DEBT;

        $loan = new Loan();
        $loan->setUser($user);
        $loan->setPartner($partner);
        $loan->setAmount($amount);
        $loan->setRemainingAmount($amount);
        $loan->setType($type);
        $loan->setDirection($direction);
        $loan->setNotes($data['notes'] ?? null);

        if ($type === Loan::TYPE_VIRTUAL) {
            if (!isset($data['operator_id'])) {
                throw new \InvalidArgumentException('L\'opérateur est obligatoire pour un prêt VIRTUEL.');
            }
            $operator = $this->em->getRepository(Operator::class)->find($data['operator_id']);
            if (!$operator) {
                throw new \InvalidArgumentException('Opérateur introuvable.');
            }
            $loan->setOperator($operator);
        }

        $this->em->persist($loan);

        // --- Trigger Balance Movement ---
        $account = $this->findTargetAccount($loan);
        // If Debt: Received money (Positive) | If Credit: Sent money (Negative)
        $adjustment = ($direction === Loan::DIRECTION_DEBT) ? $amount : "-$amount";
        $notes = ($direction === Loan::DIRECTION_DEBT) ? "RECEPTION de fonds (#{$loan->getId()})" : "ENVOI de fonds (#{$loan->getId()})";

        $this->balanceService->adjust($account, $adjustment, $user, null, $notes, BalanceMovement::TYPE_LOAN, $loan);

        $this->em->flush();

        return $loan;
    }

    /**
     * Records a repayment for a loan.
     */
    public function repayLoan(Loan $loan, string $amount, ?string $notes = null): Loan
    {
        if ($loan->getStatus() === Loan::STATUS_REPAID) {
            throw new \RuntimeException('Opération déjà soldée.');
        }

        $remaining = $loan->getRemainingAmount();
        $newRemaining = bcsub($remaining, $amount, 2);

        if (bccomp($newRemaining, '0', 2) === -1) {
            throw new \InvalidArgumentException('Le montant dépasse le reste à payer.');
        }

        $loan->setRemainingAmount($newRemaining);

        if (bccomp($newRemaining, '0', 2) === 0) {
            $loan->setStatus(Loan::STATUS_REPAID);
            $loan->setRepaidAt(new \DateTime());
        }

        // --- Trigger Balance Movement ---
        $user = $loan->getUser();
        $account = $this->findTargetAccount($loan);
        // If it was a Debt: Paying back (Negative) | If it was a Credit: Receiving payment (Positive)
        $adjustment = ($loan->getDirection() === Loan::DIRECTION_DEBT) ? "-$amount" : $amount;
        $movementNotes = $notes ?: (($loan->getDirection() === Loan::DIRECTION_DEBT) ? "REMBOURSEMENT de dette (#{$loan->getId()})" : "ENCAISSEMENT de créance (#{$loan->getId()})");

        $this->balanceService->adjust($account, $adjustment, $user, null, $movementNotes, BalanceMovement::TYPE_LOAN, $loan);

        $this->em->flush();

        return $loan;
    }

    private function findTargetAccount(Loan $loan): Account
    {
        if ($loan->getType() === Loan::TYPE_CASH) {
            $account = $this->em->getRepository(Account::class)->findOneBy(['type' => Account::TYPE_PHYSICAL]);
            if (!$account) {
                $account = new Account();
                $account->setType(Account::TYPE_PHYSICAL);
                $account->setBalance('0');
                $this->em->persist($account);
            }
            return $account;
        }

        $account = $this->em->getRepository(Account::class)->findOneBy([
            'operator' => $loan->getOperator(),
            'type' => Account::TYPE_VIRTUAL
        ]);

        if (!$account) {
            throw new \RuntimeException("Compte VIRTUEL manquant pour l'opérateur " . $loan->getOperator()->getName());
        }

        return $account;
    }
}
