<?php

namespace App\Controller;

use App\Entity\Operator;
use App\Entity\Account;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/accounts')]
class AccountController extends AbstractController
{
    #[Route('/operator/{id}', name: 'api_accounts_by_operator', methods: ['GET'])]
    public function getByOperator(Operator $operator, SerializerInterface $serializer): JsonResponse
    {
        $accounts = $operator->getBalances(); // Still named 'balances' in Operator entity for now
        $json = $serializer->serialize($accounts, 'json', ['groups' => 'account:read']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/physical', name: 'api_accounts_physical', methods: ['GET'])]
    public function getPhysical(AccountRepository $repo, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $account = $repo->findPhysicalAccount();
        
        if (!$account) {
            $account = new Account();
            $account->setType(Account::TYPE_PHYSICAL);
            $account->setBalance('0');
            $account->setCurrency('FCFA');
            $account->setOperator(null); // Explicitly null for central chest
            $em->persist($account);
            $em->flush();
        }

        return new JsonResponse(
            $serializer->serialize($account, 'json', ['groups' => 'account:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/operator/{id}', name: 'api_accounts_create', methods: ['POST'])]
    public function create(
        Request $request, 
        Operator $operator, 
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $account = new Account();
        $account->setOperator($operator);
        $account->setType($data['type'] ?? Account::TYPE_VIRTUAL);
        $account->setBalance($data['balance'] ?? '0');
        $account->setCurrency($data['currency'] ?? 'FCFA');
        $account->setNotes($data['notes'] ?? null);

        $em->persist($account);
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($account, 'json', ['groups' => 'account:read']),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{id}/adjust', name: 'api_accounts_adjust', methods: ['POST'])]
    public function adjust(
        Account $account, 
        Request $request, 
        EntityManagerInterface $em, 
        \App\Service\BalanceService $balanceService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $amount = $data['amount'] ?? '0';
        $notes = $data['notes'] ?? null;
        
        $balanceService->adjust($account, $amount, $this->getUser(), null, $notes);
        $em->flush();

        return new JsonResponse(['message' => 'Solde ajusté avec succès']);
    }

    #[Route('/{id}', name: 'api_accounts_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Request $request, 
        Account $account, 
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['balance'])) $account->setBalance($data['balance']);
        if (isset($data['notes'])) $account->setNotes($data['notes']);

        $em->flush();

        return new JsonResponse(
            $serializer->serialize($account, 'json', ['groups' => 'account:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }
}
