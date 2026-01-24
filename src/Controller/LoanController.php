<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Entity\User;
use App\Entity\Partner;
use App\Repository\LoanRepository;
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/loans')]
class LoanController extends AbstractController
{
    #[Route('', name: 'api_loans_index', methods: ['GET'])]
    public function index(LoanRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $loans = $repository->findUserLoans($user);

        return new JsonResponse(
            $serializer->serialize($loans, 'json', [
                'groups' => ['loan:read', 'partner:read', 'operator:read']
            ]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('', name: 'api_loans_create', methods: ['POST'])]
    public function create(
        Request $request,
        LoanService $loanService,
        SerializerInterface $serializer,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $partnerId = $data['partner_id'] ?? null;
        if (!$partnerId) {
            return $this->json(['error' => 'Le partenaire est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $partner = $em->getRepository(Partner::class)->find($partnerId);
        if (!$partner) {
            return $this->json(['error' => 'Partenaire introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $loan = $loanService->recordLoan($user, $partner, $data);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(
            $serializer->serialize($loan, 'json', [
                'groups' => ['loan:read', 'partner:read', 'operator:read']
            ]),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_loans_show', methods: ['GET'])]
    public function show(Loan $loan, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
        if ($loan->getUser() !== $user) {
            return $this->json(['error' => 'Action non autorisée.'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(
            $serializer->serialize($loan, 'json', [
                'groups' => ['loan:read', 'partner:read', 'operator:read']
            ]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}/repay', name: 'api_loans_repay', methods: ['POST'])]
    public function repay(
        Loan $loan,
        Request $request,
        LoanService $loanService,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $this->getUser();
        if ($loan->getUser() !== $user) {
            return $this->json(['error' => 'Action non autorisée.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $amount = $data['amount'] ?? $loan->getRemainingAmount();
        $notes = $data['notes'] ?? null;

        try {
            $loan = $loanService->repayLoan($loan, (string) $amount, $notes);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(
            $serializer->serialize($loan, 'json', [
                'groups' => ['loan:read', 'partner:read', 'operator:read']
            ]),
            Response::HTTP_OK,
            [],
            true
        );
    }
}
