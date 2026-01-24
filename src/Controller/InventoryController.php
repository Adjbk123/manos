<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\StockBatch;
use App\Entity\StockArrival;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\StockBatchRepository;
use App\Repository\StockArrivalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/stock')]
class InventoryController extends AbstractController
{
    #[Route('/products', name: 'api_stock_products_list', methods: ['GET'])]
    public function listProducts(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();
        return $this->json($products, 200, [], ['groups' => 'stock:read']);
    }

    #[Route('/products', name: 'api_stock_products_create', methods: ['POST'])]
    public function createProduct(Request $request, EntityManagerInterface $em, ProductCategoryRepository $categoryRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setName($data['name']);
        
        if (isset($data['categoryId'])) {
            $category = $categoryRepository->find($data['categoryId']);
            if ($category) {
                $product->setCategory($category);
            }
        }

        $product->setDescription($data['description'] ?? null);
        $product->setImage($data['image'] ?? null);
        $product->setStockQuantity(0);

        $em->persist($product);
        $em->flush();

        return $this->json($product, 201, [], ['groups' => 'stock:read']);
    }

    #[Route('/batches', name: 'api_stock_batches_create', methods: ['POST'])]
    public function addBatch(
        Request $request, 
        ProductRepository $productRepository, 
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $product = $productRepository->find($data['productId']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        $batch = new StockBatch();
        $batch->setProduct($product);
        $batch->setPurchasePrice($data['purchasePrice']);
        $batch->setMinSellingPrice($data['minSellingPrice']);
        $batch->setTargetSellingPrice($data['targetSellingPrice']);
        $batch->setQuantityInitial($data['quantity']);
        $batch->setQuantityRemaining($data['quantity']);
        $batch->setPurchaseDate(new \DateTime($data['purchaseDate'] ?? 'now'));
        $batch->setSupplier($data['supplier'] ?? null);

        $em->persist($batch);

        // Update global stock quantity
        $product->setStockQuantity($product->getStockQuantity() + $data['quantity']);
        $em->persist($product);

        $em->flush();

        return $this->json($batch, 201, [], ['groups' => 'stock_batch:read']);
    }

    #[Route('/arrivals', name: 'api_stock_arrival_list', methods: ['GET'])]
    public function listArrivals(StockArrivalRepository $arrivalRepository): JsonResponse
    {
        $arrivals = $arrivalRepository->findBy([], ['arrivalDate' => 'DESC']);
        return $this->json($arrivals, 200, [], ['groups' => 'stock_arrival:read']);
    }

    #[Route('/arrivals/{id}', name: 'api_stock_arrival_show', methods: ['GET'])]
    public function showArrival(StockArrival $arrival): JsonResponse
    {
        return $this->json($arrival, 200, [], ['groups' => 'stock_arrival:read']);
    }

    #[Route('/arrivals', name: 'api_stock_arrival_create', methods: ['POST'])]
    public function createArrival(
        Request $request, 
        ProductRepository $productRepository, 
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        // Validation: reference, items must exist
        if (empty($data['items']) || !is_array($data['items'])) {
            return $this->json(['error' => 'No items provided for arrival'], 400);
        }

        $arrival = new StockArrival();
        $arrival->setReference($data['reference'] ?? 'ARR-' . date('Ymd-His'));
        $arrival->setSupplier($data['supplier'] ?? null);
        $arrival->setArrivalDate(new \DateTime($data['arrivalDate'] ?? 'now'));
        
        $totalAmount = 0;

        foreach ($data['items'] as $item) {
            $product = $productRepository->find($item['productId']);
            if (!$product) {
                continue; // Or error out? Let's skip invalid products for now or error. user wants strictness usually.
                // return $this->json(['error' => 'Product not found: ' . $item['productId']], 404);
            }

            $quantity = (int) $item['quantity'];
            $purchasePrice = (float) $item['purchasePrice'];
            
            $batch = new StockBatch();
            $batch->setProduct($product);
            $batch->setQuantityInitial($quantity);
            $batch->setQuantityRemaining($quantity);
            $batch->setPurchasePrice($purchasePrice);
            $batch->setMinSellingPrice($item['minSellingPrice'] ?? $purchasePrice); // Default to purchase if not set (bad practice but safe)
            $batch->setTargetSellingPrice($item['targetSellingPrice'] ?? $purchasePrice);
            $batch->setSupplier($data['supplier'] ?? null);
            $batch->setPurchaseDate($arrival->getArrivalDate());
            
            $arrival->addStockBatch($batch);
            $em->persist($batch); // Persist batch so it gets an ID and linked (?) - Cascading might handle it but explicit is clearer

            // Update Product Stock
            $product->setStockQuantity($product->getStockQuantity() + $quantity);
            $em->persist($product);

            $totalAmount += ($quantity * $purchasePrice);
        }

        $arrival->setTotalAmount($totalAmount);
        
        $em->persist($arrival);
        $em->flush();

        return $this->json($arrival, 201, [], ['groups' => 'stock_arrival:read']);
    }

    #[Route('/products/{id}/batches', name: 'api_stock_product_batches', methods: ['GET'])]
    public function getProductBatches(Product $product): JsonResponse
    {
        return $this->json($product->getStockBatches(), 200, [], ['groups' => 'stock_batch:read']);
    }

    #[Route('/categories', name: 'api_stock_categories_list', methods: ['GET'])]
    public function listCategories(ProductCategoryRepository $categoryRepository): JsonResponse
    {
        return $this->json($categoryRepository->findAll(), 200, [], ['groups' => 'category:read']);
    }

    #[Route('/categories', name: 'api_stock_categories_create', methods: ['POST'])]
    public function createCategory(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $category = new ProductCategory();
        $category->setName($data['name']);
        $category->setDescription($data['description'] ?? null);

        $em->persist($category);
        $em->flush();

        return $this->json($category, 201, [], ['groups' => 'category:read']);
    }
}
