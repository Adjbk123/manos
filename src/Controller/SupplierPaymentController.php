<?php

namespace App\Controller;

use App\Entity\SupplierPayment;
use App\Repository\SupplierRepository;
use App\Repository\SupplierPaymentRepository;
use App\Service\ShopCashService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/supplier-payments')]
class SupplierPaymentController extends AbstractController
{
    #[Route('', name: 'api_supplier_payments_list', methods: ['GET'])]
    public function list(SupplierPaymentRepository $repository): JsonResponse
    {
        $payments = $repository->findBy([], ['paymentDate' => 'DESC']);
        return $this->json($payments, 200, [], ['groups' => 'supplier_payment:read']);
    }

    #[Route('', name: 'api_supplier_payments_create', methods: ['POST'])]
    public function create(
        Request $request, 
        SupplierRepository $supplierRepository,
        EntityManagerInterface $em,
        ShopCashService $cashService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $supplier = $supplierRepository->find($data['supplierId']);
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], 404);
        }

        $payment = new SupplierPayment();
        $payment->setSupplier($supplier);
        $payment->setAmount((string)$data['amount']);
        $payment->setNote($data['note'] ?? null);
        $payment->setCreatedBy($this->getUser());
        $payment->setPaymentDate(new \DateTime($data['paymentDate'] ?? 'now'));

        // Update supplier balance
        $currentBalance = (float)$supplier->getBalance();
        $newBalance = $currentBalance - (float)$data['amount'];
        $supplier->setBalance((string)$newBalance);

        $em->persist($payment);
        $em->persist($supplier);
        
        // Record Shop Cash Movement
        $cashService->addMovement(
            'OUT',
            (float)$data['amount'],
            "Règlement fournisseur : " . $supplier->getName() . ($payment->getNote() ? " (" . $payment->getNote() . ")" : ""),
            'SUPPLIER_PAYMENT',
            $payment->getId(),
            $this->getUser()
        );

        $em->flush();

        return $this->json($payment, 201, [], ['groups' => 'supplier_payment:read']);
    }
}
