<?php

namespace App\Controller;

use App\Entity\Partner;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/partners')]
class PartnerController extends AbstractController
{
    #[Route('', name: 'api_partners_index', methods: ['GET'])]
    public function index(PartnerRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $partners = $repository->findBy([], ['name' => 'ASC']);
        return new JsonResponse(
            $serializer->serialize($partners, 'json', ['groups' => 'partner:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('', name: 'api_partners_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $partner = new Partner();
        $partner->setName($data['name'] ?? '');
        $partner->setType($data['type'] ?? 'STRUCTURE');
        $partner->setPhone($data['phone'] ?? null);

        if (empty($partner->getName())) {
            return $this->json(['error' => 'Le nom est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($partner);
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($partner, 'json', ['groups' => 'partner:read']),
            Response::HTTP_CREATED,
            [],
            true
        );
    }
}
