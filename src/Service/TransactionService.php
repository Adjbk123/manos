<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Operator;
use App\Entity\OperationType;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Service\BalanceService;

class TransactionService
{
    private EntityManagerInterface $em;
    private BalanceService $balanceService;

    public function __construct(EntityManagerInterface $em, BalanceService $balanceService)
    {
        $this->em = $em;
        $this->balanceService = $balanceService;
    }

    public function createTransaction(User $user, array $data): Transaction
    {
        $opType = $this->em->getRepository(OperationType::class)->find($data['operation_type_id'] ?? 0);
        if (!$opType) {
            throw new NotFoundHttpException('Service introuvable');
        }

        $operator = $this->em->getRepository(Operator::class)->find($data['operator_id'] ?? 0);
        if (!$operator) {
            throw new NotFoundHttpException('Opérateur introuvable');
        }

        $customer = null;
        if (!empty($data['customer_phone'])) {
            $customer = $this->em->getRepository(Customer::class)->findOneBy(['phone' => $data['customer_phone']]);
            if (!$customer) {
                $customer = new Customer();
                $customer->setPhone($data['customer_phone']);
                $this->em->persist($customer);
            }

            if (!empty($data['customer_nom'])) {
                $customer->setNom($data['customer_nom']);
            }
            if (!empty($data['customer_prenom'])) {
                $customer->setPrenom($data['customer_prenom']);
            }
        }

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setCustomer($customer);
        $transaction->setOperator($operator);
        $transaction->setOperationType($opType);
        $transaction->setAmount($data['amount']);
        $transaction->setNotes($data['notes'] ?? null);

        $status = $data['status'] ?? Transaction::STATUS_SUCCESS;
        $transaction->setStatus($status);

        $this->em->persist($transaction);

        if ($status === Transaction::STATUS_SUCCESS) {
            $this->balanceService->triggerTransactionMovements($transaction);
        }

        $this->em->flush();

        return $transaction;
    }
}
