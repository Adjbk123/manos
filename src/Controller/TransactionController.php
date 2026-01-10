<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Customer;
use App\Entity\OperationType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/transactions')]
class TransactionController extends AbstractController
{
    #[Route('', name: 'api_transactions_index', methods: ['GET'])]
    public function index(Request $request, TransactionRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $customerId = $request->query->get('customerId');
        $params = [];
        if ($customerId) {
            $params['customer'] = $customerId;
        }

        $transactions = $repository->findBy($params, ['createdAt' => 'DESC'], 50);
        return new JsonResponse(
            $serializer->serialize($transactions, 'json', [
                'groups' => ['transaction:read', 'customer:read', 'operation_type:read']
            ]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('', name: 'api_transactions_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $opType = $em->getRepository(OperationType::class)->find($data['operation_type_id'] ?? 0);
        if (!$opType) {
            return $this->json(['error' => 'Operation Type not found'], Response::HTTP_NOT_FOUND);
        }

        $customer = null;
        if (!empty($data['customer_phone'])) {
            $customer = $em->getRepository(Customer::class)->findOneBy(['phone' => $data['customer_phone']]);
            if (!$customer) {
                $customer = new Customer();
                $customer->setPhone($data['customer_phone']);
                $customer->setNom($data['customer_nom'] ?? null);
                $em->persist($customer);
            }
        }

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setCustomer($customer);
        $transaction->setOperationType($opType);
        $transaction->setAmount($data['amount']);
        $transaction->setNotes($data['notes'] ?? null);
        $transaction->setStatus(Transaction::STATUS_PENDING);

        $em->persist($transaction);
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($transaction, 'json', [
                'groups' => ['transaction:read', 'customer:read', 'operation_type:read']
            ]),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{id}/status', name: 'api_transactions_update_status', methods: ['PATCH'])]
    public function updateStatus(
        Transaction $transaction,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        \App\Service\BalanceService $balanceService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $oldStatus = $transaction->getStatus();
        if (isset($data['status'])) {
            $newStatus = $data['status'];
            $transaction->setStatus($newStatus);

            // If transaction just became successful, reconcile balances
            if ($newStatus === Transaction::STATUS_SUCCESS && $oldStatus !== Transaction::STATUS_SUCCESS) {
                $balanceService->triggerTransactionMovements($transaction);
            }
        }

        $em->flush();

        return new JsonResponse(
            $serializer->serialize($transaction, 'json', [
                'groups' => ['transaction:read', 'customer:read', 'operation_type:read']
            ]),
            Response::HTTP_OK,
            [],
            true
        );
    }
}
