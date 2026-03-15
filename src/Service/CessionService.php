<?php

namespace App\Service;

use App\Entity\Cession;
use App\Entity\User;
use App\Entity\Partner;
use App\Entity\Operator;
use App\Entity\Account;
use App\Entity\BalanceMovement;
use Doctrine\ORM\EntityManagerInterface;

class CessionService
{
    private EntityManagerInterface $em;
    private BalanceService $balanceService;

    public function __construct(EntityManagerInterface $em, BalanceService $balanceService)
    {
        $this->em = $em;
        $this->balanceService = $balanceService;
    }

    /**
     * Records a new cession (fund swap) between the agent and a partner merchant.
     */
    public function recordCession(User $user, Partner $partner, array $data): Cession
    {
        $amountCeded = $data['amount_ceded'] ?? null;
        $amountReceived = $data['amount_received'] ?? null;
        $typeReceived = $data['type_received'] ?? 'CASH';

        if (!$amountCeded || bccomp($amountCeded, '0', 2) <= 0) {
            throw new \InvalidArgumentException('Le montant cédé doit être supérieur à zéro.');
        }

        if (!$amountReceived || bccomp($amountReceived, '0', 2) <= 0) {
            throw new \InvalidArgumentException('Le montant reçu doit être supérieur à zéro.');
        }

        $operatorSource = $this->em->getRepository(Operator::class)->find($data['operator_source_id']);
        if (!$operatorSource) {
            throw new \InvalidArgumentException('Opérateur source introuvable.');
        }

        $cession = new Cession();
        $cession->setUser($user);
        $cession->setPartner($partner);
        $cession->setOperatorSource($operatorSource);
        $cession->setAmountCeded($amountCeded);
        $cession->setAmountReceived($amountReceived);
        $cession->setTypeReceived($typeReceived);
        $cession->setNotes($data['notes'] ?? null);

        if ($typeReceived === 'VIRTUEL') {
            if (!isset($data['operator_received_id'])) {
                throw new \InvalidArgumentException('L\'opérateur de réception est requis pour une cession virtuelle.');
            }
            $opRec = $this->em->getRepository(Operator::class)->find($data['operator_received_id']);
            if (!$opRec) {
                throw new \InvalidArgumentException('Opérateur de réception introuvable.');
            }
            $cession->setOperatorReceived($opRec);
        }

        $this->em->persist($cession);

        // --- Trigger Balance Movements ---

        // 1. Source Account: Decrease UV
        $sourceAccount = $this->em->getRepository(Account::class)->findOneBy([
            'operator' => $operatorSource,
            'type' => Account::TYPE_VIRTUAL
        ]);
        if (!$sourceAccount) {
            throw new \RuntimeException("Compte VIRTUEL source manquant pour {$operatorSource->getName()}");
        }

        $this->balanceService->adjust(
            $sourceAccount, 
            "-$amountCeded", 
            $user, 
            null, 
            "CESSION de funds ({$operatorSource->getName()}) vers {$partner->getName()}", 
            BalanceMovement::TYPE_CESSION, 
            null, 
            $cession
        );

        // 2. Received Account: Increase Cash or UV
        $receivedAccount = null;
        if ($typeReceived === 'CASH') {
            $receivedAccount = $this->em->getRepository(Account::class)->findOneBy(['type' => Account::TYPE_PHYSICAL]);
            if (!$receivedAccount) {
                $receivedAccount = new Account();
                $receivedAccount->setType(Account::TYPE_PHYSICAL);
                $receivedAccount->setBalance('0');
                $this->em->persist($receivedAccount);
            }
        } else {
            $receivedAccount = $this->em->getRepository(Account::class)->findOneBy([
                'operator' => $cession->getOperatorReceived(),
                'type' => Account::TYPE_VIRTUAL
            ]);
            if (!$receivedAccount) {
                throw new \RuntimeException("Compte VIRTUEL de réception manquant pour {$cession->getOperatorReceived()->getName()}");
            }
        }

        $destLabel = ($typeReceived === 'CASH') ? "CASH" : $cession->getOperatorReceived()->getName();
        $this->balanceService->adjust(
            $receivedAccount, 
            $amountReceived, 
            $user, 
            null, 
            "RECEPTION contrepartie cession ({$destLabel}) de {$partner->getName()}", 
            BalanceMovement::TYPE_CESSION, 
            null, 
            $cession
        );

        $this->em->flush();

        return $cession;
    }
}
