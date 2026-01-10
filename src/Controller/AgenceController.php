<?php

namespace App\Controller;

use App\Entity\Agence;
use App\Repository\AgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/agences', name: 'api_agences_')]
class AgenceController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(AgenceRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $agences = $repository->findAll();
        $json = $serializer->serialize($agences, 'json', ['groups' => 'agence:read']);
        
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $agence = new Agence();
        $agence->setNom($data['nom'] ?? '');
        $agence->setAdresse($data['adresse'] ?? '');
        $agence->setContact($data['contact'] ?? '');
        $agence->setLogo($data['logo'] ?? null);
        
        $em->persist($agence);
        $em->flush();
        
        $json = $serializer->serialize($agence, 'json', ['groups' => 'agence:read']);
        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Agence $agence, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($agence, 'json', ['groups' => 'agence:read']);
        return new JsonResponse($json, 200, [], true);
    }
}
