<?php

namespace App\Controller;

use App\Entity\StockAdjustment;
use App\Repository\ProductRepository;
use App\Repository\StockBatchRepository;
use App\Repository\StockAdjustmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/stock-adjustments')]
class StockAdjustmentController extends AbstractController
{
    #[Route('', name: 'api_stock_adjustments_list', methods: ['GET'])]
    public function list(StockAdjustmentRepository $repository): JsonResponse
    {
        $adjustments = $repository->findBy([], ['createdAt' => 'DESC']);
        return $this->json($adjustments, 200, [], ['groups' => 'stock_adjustment:read']);
    }

    #[Route('', name: 'api_stock_adjustments_create', methods: ['POST'])]
    public function create(
        Request $request,
        ProductRepository $productRepository,
        StockBatchRepository $batchRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $product = $productRepository->find($data['productId']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        $quantityToRemove = (int)$data['quantity'];
        if ($quantityToRemove <= 0) {
            return $this->json(['error' => 'Invalid quantity'], 400);
        }

        if ($product->getStockQuantity() < $quantityToRemove) {
            return $this->json(['error' => 'Insufficient stock'], 400);
        }

        // Apply FIFO removal from batches
        $batches = $batchRepository->findBy(
            ['product' => $product],
            ['purchaseDate' => 'ASC']
        );

        $remainingToRemove = $quantityToRemove;
        $adjustmentsCreated = [];

        foreach ($batches as $batch) {
            if ($remainingToRemove <= 0) break;
            
            $available = $batch->getQuantityRemaining();
            if ($available <= 0) continue;

            $take = min($available, $remainingToRemove);

            $adjustment = new StockAdjustment();
            $adjustment->setProduct($product);
            $adjustment->setBatch($batch);
            $adjustment->setQuantity($take);
            $adjustment->setType($data['type'] ?? 'LOSS');
            $adjustment->setReason($data['reason'] ?? null);
            $adjustment->setCreatedBy($this->getUser());

            $batch->setQuantityRemaining($available - $take);
            $remainingToRemove -= $take;

            $em->persist($adjustment);
            $em->persist($batch);
            $adjustmentsCreated[] = $adjustment;
        }

        // Update Global Product Stock
        $product->setStockQuantity($product->getStockQuantity() - $quantityToRemove);
        $em->persist($product);

        $em->flush();

        return $this->json($adjustmentsCreated, 201, [], ['groups' => 'stock_adjustment:read']);
    }
}
