<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/suppliers')]
class SupplierController extends AbstractController
{
    #[Route('', name: 'api_suppliers_list', methods: ['GET'])]
    public function list(SupplierRepository $repository): JsonResponse
    {
        $suppliers = $repository->findBy([], ['name' => 'ASC']);
        return $this->json($suppliers, 200, [], ['groups' => 'supplier:read']);
    }

    #[Route('', name: 'api_suppliers_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $supplier = new Supplier();
        $supplier->setName($data['name']);
        $supplier->setPhone($data['phone'] ?? null);
        $supplier->setAddress($data['address'] ?? null);
        $supplier->setBalance($data['balance'] ?? '0.00');

        $em->persist($supplier);
        $em->flush();

        return $this->json($supplier, 201, [], ['groups' => 'supplier:read']);
    }

    #[Route('/{id}', name: 'api_suppliers_update', methods: ['PUT'])]
    public function update(int $id, Request $request, SupplierRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $supplier = $repository->find($id);
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $supplier->setName($data['name'] ?? $supplier->getName());
        $supplier->setPhone($data['phone'] ?? $supplier->getPhone());
        $supplier->setAddress($data['address'] ?? $supplier->getAddress());

        $em->flush();

        return $this->json($supplier, 200, [], ['groups' => 'supplier:read']);
    }

    #[Route('/{id}', name: 'api_suppliers_delete', methods: ['DELETE'])]
    public function delete(int $id, SupplierRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $supplier = $repository->find($id);
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], 404);
        }

        $em->remove($supplier);
        $em->flush();

        return $this->json(['message' => 'Supplier deleted'], 200);
    }
}
