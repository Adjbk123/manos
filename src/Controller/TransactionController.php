<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Customer;
use App\Entity\OperationType;
use App\Entity\Operator;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\TransactionService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        SerializerInterface $serializer,
        TransactionService $transactionService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        try {
            $transaction = $transactionService->createTransaction($user, $data);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

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
        try {
            if (isset($data['status'])) {
                $newStatus = $data['status'];
                $transaction->setStatus($newStatus);

                // If transaction just became successful, reconcile balances
                if ($newStatus === Transaction::STATUS_SUCCESS && $oldStatus !== Transaction::STATUS_SUCCESS) {
                    $balanceService->triggerTransactionMovements($transaction);
                }

                // If transaction is cancelled, reverse balance movements
                if ($newStatus === Transaction::STATUS_CANCELLED && $oldStatus === Transaction::STATUS_SUCCESS) {
                    $balanceService->triggerTransactionReversal($transaction);
                }
            }

            $em->flush();
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

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
