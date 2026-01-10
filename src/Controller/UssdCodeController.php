<?php

namespace App\Controller;

use App\Entity\UssdCode;
use App\Entity\Operator;
use App\Entity\OperationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/ussd-codes')]
class UssdCodeController extends AbstractController
{
    #[Route('', name: 'api_ussd_codes_create', methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $operator = $em->getRepository(Operator::class)->find($data['operator_id'] ?? 0);
        $opType = $em->getRepository(OperationType::class)->find($data['operation_type_id'] ?? 0);

        if (!$operator || !$opType) {
            return $this->json(['error' => 'Operator or Operation Type not found'], Response::HTTP_NOT_FOUND);
        }

        $ussd = new UssdCode();
        $ussd->setOperator($operator);
        $ussd->setOperationType($opType);
        $ussd->setTemplate($data['template']);
        $ussd->setParameters($data['parameters'] ?? null);
        $ussd->setMethod($data['method'] ?? 'USSD');
        $ussd->setNotes($data['notes'] ?? null);

        $em->persist($ussd);
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($ussd, 'json', ['groups' => 'ussd_code:read']),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_ussd_codes_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Request $request, 
        UssdCode $ussd, 
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['template'])) $ussd->setTemplate($data['template']);
        if (isset($data['parameters'])) $ussd->setParameters($data['parameters']);
        if (isset($data['notes'])) $ussd->setNotes($data['notes']);
        if (isset($data['method'])) $ussd->setMethod($data['method']);

        $em->flush();

        return new JsonResponse(
            $serializer->serialize($ussd, 'json', ['groups' => 'ussd_code:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_ussd_codes_delete', methods: ['DELETE'])]
    public function delete(UssdCode $ussd, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($ussd);
        $em->flush();
        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }
}
