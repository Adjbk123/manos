<?php

namespace App\Controller;

use App\Entity\Cession;
use App\Entity\User;
use App\Entity\Partner;
use App\Repository\CessionRepository;
use App\Service\CessionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/cessions')]
class CessionController extends AbstractController
{
    #[Route('', name: 'api_cessions_index', methods: ['GET'])]
    public function index(CessionRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $cessions = $repository->findBy([], ['createdAt' => 'DESC']);

        return new JsonResponse(
            $serializer->serialize($cessions, 'json', [
                'groups' => ['cession:read', 'partner:read', 'operator:read']
            ]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('', name: 'api_cessions_create', methods: ['POST'])]
    public function create(
        Request $request,
        CessionService $cessionService,
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
            $cession = $cessionService->recordCession($user, $partner, $data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(
            $serializer->serialize($cession, 'json', [
                'groups' => ['cession:read', 'partner:read', 'operator:read']
            ]),
            Response::HTTP_CREATED,
            [],
            true
        );
    }
}
