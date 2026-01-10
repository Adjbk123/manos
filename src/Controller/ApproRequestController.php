<?php

namespace App\Controller;

use App\Repository\ApproRequestRepository;
use App\Service\ApprovisionnementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Route('/api/appro-requests')]
class ApproRequestController extends AbstractController
{
    private ApprovisionnementService $approService;
    private SerializerInterface $serializer;

    public function __construct(ApprovisionnementService $approService, SerializerInterface $serializer)
    {
        $this->approService = $approService;
        $this->serializer = $serializer;
    }

    #[Route('', name: 'app_appro_request_index', methods: ['GET'])]
    public function index(Request $request, ApproRequestRepository $repo, \Doctrine\ORM\EntityManagerInterface $em): JsonResponse
    {
        $criteria = [];
        
        // Security: Filter by User Role
        $user = $this->getUser();
        if ($user && !in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            // Agent sees only requests targeting them (as they are the validators/receivers)
            $criteria['agent'] = $user;
        } elseif ($agentId = $request->query->get('agent_id')) {
            // Admin can filter by agent
            $criteria['agent'] = $em->getRepository(\App\Entity\User::class)->find($agentId);
        }

        // Operator Filter
        if ($operatorId = $request->query->get('operator_id')) {
            $criteria['operator'] = $em->getRepository(\App\Entity\Operator::class)->find($operatorId);
        }

        // Status Filter
        if ($status = $request->query->get('status')) {
            $criteria['status'] = $status;
        }

        $requests = $repo->findBy($criteria, ['createdAt' => 'DESC']);
        
        $json = $this->serializer->serialize($requests, 'json', ['groups' => 'appro_request:read']);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('', name: 'app_appro_request_new', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $approRequest = $this->approService->createRequest($this->getUser(), $data);
            
            $json = $this->serializer->serialize($approRequest, 'json', ['groups' => 'appro_request:read']);
            return new JsonResponse($json, 201, [], true);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur inattendue', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/validate', name: 'app_appro_request_validate', methods: ['POST'])]
    public function validate(int $id): JsonResponse
    {
        try {
            $approRequest = $this->approService->validateRequest($this->getUser(), $id);
            
            $json = $this->serializer->serialize($approRequest, 'json', ['groups' => 'appro_request:read']);
            return new JsonResponse($json, 200, [], true);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    #[Route('/{id}/reject', name: 'app_appro_request_reject', methods: ['POST'])]
    public function reject(int $id): JsonResponse
    {
        try {
            $approRequest = $this->approService->rejectRequest($this->getUser(), $id);
            
            $json = $this->serializer->serialize($approRequest, 'json', ['groups' => 'appro_request:read']);
            return new JsonResponse($json, 200, [], true);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}
