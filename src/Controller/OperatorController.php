<?php

namespace App\Controller;

use App\Entity\Operator;
use App\Repository\OperatorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/operators')]
class OperatorController extends AbstractController
{
    #[Route('', name: 'api_operators_index', methods: ['GET'])]
    public function index(OperatorRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $operators = $repository->findAll();
        $json = $serializer->serialize($operators, 'json', ['groups' => ['operator:read']]);
        
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'api_operators_create', methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em, 
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $name = $request->request->get('name');
        $status = $request->request->get('status');
        $file = $request->files->get('logo');

        $operator = new Operator();
        $operator->setName($name);
        $operator->setStatus($status === 'true' || $status === '1' || $status === true);

        if ($file) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/operators';
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }
            $fileName = uniqid() . '.' . $file->guessExtension();
            $file->move($uploadsDir, $fileName);
            $operator->setLogo('/uploads/operators/' . $fileName);
        }

        $errors = $validator->validate($operator);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $em->persist($operator);
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($operator, 'json', ['groups' => 'operator:read']),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_operators_show', methods: ['GET'])]
    public function show(Operator $operator, SerializerInterface $serializer): JsonResponse
    {
        // On inclue les relations pour avoir le détail complet (balances, types, ussd)
        $json = $serializer->serialize($operator, 'json', [
            'groups' => [
                'operator:read', 
                'operation_type:read', 
                'ussd_code:read', 
                'operator_balance:read'
            ]
        ]);
        
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_operators_update', methods: ['POST', 'PUT', 'PATCH'])]
    public function update(
        Request $request, 
        Operator $operator, 
        EntityManagerInterface $em, 
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $name = $request->request->get('name');
        $status = $request->request->get('status');
        $file = $request->files->get('logo');
        
        if ($name !== null) $operator->setName($name);
        if ($status !== null) $operator->setStatus($status === 'true' || $status === '1' || $status === true);

        if ($file) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/operators';
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }
            
            // On pourrait supprimer l'ancien fichier ici
            $fileName = uniqid() . '.' . $file->guessExtension();
            $file->move($uploadsDir, $fileName);
            $operator->setLogo('/uploads/operators/' . $fileName);
        }

        $errors = $validator->validate($operator);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return new JsonResponse(
            $serializer->serialize($operator, 'json', ['groups' => 'operator:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }
}
