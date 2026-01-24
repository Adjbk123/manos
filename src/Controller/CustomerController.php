<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/customers')]
class CustomerController extends AbstractController
{
    #[Route('', name: 'api_customers_index', methods: ['GET'])]
    public function index(CustomerRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $customers = $repository->findAll();
        return new JsonResponse(
            $serializer->serialize($customers, 'json', ['groups' => 'customer:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/phone/{phone}', name: 'api_customers_by_phone', methods: ['GET'])]
    public function byPhone(string $phone, CustomerRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $customer = $repository->findOneBy(['phone' => $phone]);
        if (!$customer) {
            return $this->json(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            $serializer->serialize($customer, 'json', ['groups' => 'customer:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_customers_show', methods: ['GET'])]
    public function show(Customer $customer, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($customer, 'json', ['groups' => 'customer:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('', name: 'api_customers_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $phone = $data['phone'] ?? null;

        if (!$phone) {
            return $this->json(['error' => 'Phone number is required'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si le client existe déjà
        $existingCustomer = $em->getRepository(Customer::class)->findOneBy(['phone' => $phone]);
        if ($existingCustomer) {
            return new JsonResponse(
                $serializer->serialize($existingCustomer, 'json', ['groups' => 'customer:read']),
                Response::HTTP_OK,
                [],
                true
            );
        }

        $customer = new Customer();
        $customer->setPhone($phone);
        $customer->setNom($data['nom'] ?? null);
        $customer->setPrenom($data['prenom'] ?? null);

        $em->persist($customer);
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($customer, 'json', ['groups' => 'customer:read']),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_customers_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Customer $customer,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (isset($data['phone'])) $customer->setPhone($data['phone']);
        if (isset($data['nom'])) $customer->setNom($data['nom']);
        if (isset($data['prenom'])) $customer->setPrenom($data['prenom']);

        $em->flush();

        return new JsonResponse(
            $serializer->serialize($customer, 'json', ['groups' => 'customer:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_customers_delete', methods: ['DELETE'])]
    public function delete(Customer $customer, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($customer);
        $em->flush();

        return $this->json(['message' => 'Customer deleted successfully']);
    }
}
