<?php

namespace App\Controller;

use App\Entity\OperationType;

use App\Repository\OperationTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/operation-types')]
class OperationTypeController extends AbstractController
{
    #[Route('', name: 'api_operation_types_index', methods: ['GET'])]
    public function index(OperationTypeRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $types = $repository->findAll();
        return new JsonResponse(
            $serializer->serialize($types, 'json', ['groups' => 'operation_type:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('', name: 'api_operation_types_create', methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $type = new OperationType();
        $type->setName($data['name']);
        $type->setDescription($data['description'] ?? null);
        $type->setCategory($data['category'] ?? null);

        $em->persist($type);
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($type, 'json', ['groups' => 'operation_type:read']),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_operation_types_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Request $request, 
        OperationType $type, 
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) $type->setName($data['name']);
        if (isset($data['description'])) $type->setDescription($data['description']);
        if (isset($data['category'])) $type->setCategory($data['category']);

        $em->flush();

        return new JsonResponse(
            $serializer->serialize($type, 'json', ['groups' => 'operation_type:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_operation_types_delete', methods: ['DELETE'])]
    public function delete(OperationType $type, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($type);
        $em->flush();
        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }
}
