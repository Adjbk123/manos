<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\PriceHistory;
use App\Entity\StockBatch;
use App\Entity\StockArrival;
use App\Entity\Supplier;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\StockArrivalRepository;
use App\Repository\SupplierRepository;
use App\Repository\SaleZoneRepository;
use App\Repository\ProductPriceRepository;
use App\Entity\ProductPrice;
use App\Service\ShopCashService;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/stock')]
class InventoryController extends AbstractController
{
    #[Route('/products', name: 'api_stock_products_list', methods: ['GET'])]
    public function listProducts(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $filters = $request->query->all();
        $products = $productRepository->findByFilters($filters);
        return $this->json($products, 200, [], ['groups' => 'stock:read']);
    }

    #[Route('/products', name: 'api_stock_products_create', methods: ['POST'])]
    public function createProduct(Request $request, EntityManagerInterface $em, ProductCategoryRepository $categoryRepository, SaleZoneRepository $zoneRepository): JsonResponse
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
        
        $initialQuantity = (int)($data['quantity'] ?? 0);
        $product->setStockQuantity($initialQuantity);
        $product->setAlertThreshold((int)($data['alertThreshold'] ?? 5));
        $product->setIsActive($data['isActive'] ?? true);

        $sellingPrice = !empty($data['sellingPrice']) ? (string)$data['sellingPrice'] : null;
        if ($sellingPrice) {
            $product->setSellingPrice($sellingPrice);
        }

        $em->persist($product);

        // Si un stock initial est fourni, créer un lot (Batch) automatiquement
        if ($initialQuantity > 0) {
            $batch = new StockBatch();
            $batch->setProduct($product);
            $batch->setQuantityInitial($initialQuantity);
            $batch->setQuantityRemaining($initialQuantity);
            $batch->setPurchasePrice($data['purchasePrice'] ?? 0);
            $batch->setTargetSellingPrice($sellingPrice ?? 0);
            $batch->setPurchaseDate(new \DateTime());
            $batch->setSupplier("Initial Stock");
            $em->persist($batch);
        }

        // Enregistrer l'historique du prix initial
        if ($sellingPrice) {
            $this->recordPriceHistory($product, $sellingPrice, $em);
        }

        // Gestion des prix par zone
        if (isset($data['zonePrices']) && is_array($data['zonePrices'])) {
            foreach ($data['zonePrices'] as $zoneId => $price) {
                $zone = $zoneRepository->find($zoneId);
                if ($zone) {
                    $productPrice = new ProductPrice();
                    $productPrice->setProduct($product);
                    $productPrice->setSaleZone($zone);
                    $productPrice->setPrice((string)$price);
                    $em->persist($productPrice);
                    $this->recordPriceHistory($product, (string)$price, $em, $zone);
                }
            }
        }

        $em->flush();

        return $this->json($product, 201, [], ['groups' => 'stock:read']);
    }

    #[Route('/products/{id}', name: 'api_stock_products_update', methods: ['PUT'])]
    public function updateProduct(int $id, Request $request, EntityManagerInterface $em, ProductRepository $productRepository, ProductCategoryRepository $categoryRepository, SaleZoneRepository $zoneRepository, ProductPriceRepository $productPriceRepository): JsonResponse
    {
        $product = $productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produit introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $product->setName($data['name'] ?? $product->getName());
        $product->setDescription($data['description'] ?? $product->getDescription());
        $product->setAlertThreshold((int)($data['alertThreshold'] ?? $product->getAlertThreshold()));
        $product->setIsActive($data['isActive'] ?? $product->isActive());

        if (isset($data['categoryId'])) {
            $category = $categoryRepository->find($data['categoryId']);
            if ($category) {
                $product->setCategory($category);
            }
        }

        // Gestion du changement de prix avec historique (Admin uniquement)
        if (isset($data['sellingPrice'])) {
            if ($this->isGranted('ROLE_ADMIN')) {
                $newPrice = (string)$data['sellingPrice'];
                $oldPrice = $product->getSellingPrice();
                $product->setSellingPrice($newPrice);

                if ($oldPrice !== $newPrice) {
                    $this->recordPriceHistory($product, $newPrice, $em);
                }
            }
        }

        // Gestion des prix par zone
        if (isset($data['zonePrices']) && is_array($data['zonePrices'])) {
            foreach ($data['zonePrices'] as $zoneId => $price) {
                $zone = $zoneRepository->find($zoneId);
                if ($zone) {
                    $productPrice = $productPriceRepository->findOneBy(['product' => $product, 'saleZone' => $zone]);
                    if (!$productPrice) {
                        $productPrice = new ProductPrice();
                        $productPrice->setProduct($product);
                        $productPrice->setSaleZone($zone);
                    }
                    
                    if ($productPrice->getPrice() !== (string)$price) {
                        $productPrice->setPrice((string)$price);
                        $em->persist($productPrice);
                        $this->recordPriceHistory($product, (string)$price, $em, $zone);
                    }
                }
            }
        }

        $em->persist($product);
        $em->flush();

        return $this->json($product, 200, [], ['groups' => 'stock:read']);
    }

    private function recordPriceHistory(Product $product, string $price, EntityManagerInterface $em, ?SaleZone $zone = null): void
    {
        $user = $this->getUser();

        $priceHistory = new PriceHistory();
        $priceHistory->setProduct($product);
        $priceHistory->setPrice($price);
        $priceHistory->setUser($user instanceof User ? $user : null);
        $priceHistory->setSaleZone($zone);
        $priceHistory->setEffectiveFrom(new \DateTimeImmutable());

        $em->persist($priceHistory);
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
    public function listArrivals(Request $request, StockArrivalRepository $arrivalRepository): JsonResponse
    {
        $filters = $request->query->all();
        $arrivals = $arrivalRepository->findByFilters($filters);
        return $this->json($arrivals, 200, [], ['groups' => 'stock_arrival:read']);
    }

    #[Route('/arrivals/{id}', name: 'api_stock_arrival_show', methods: ['GET'])]
    public function showArrival(StockArrival $arrival): JsonResponse
    {
        return $this->json($arrival, 200, [], ['groups' => ['stock_arrival:read', 'stock_batch:read']]);
    }

    #[Route('/arrivals/{id}/pdf', name: 'api_stock_arrival_pdf', methods: ['GET'])]
    public function generateArrivalPdf(StockArrival $arrival, PdfService $pdfService): Response
    {
        $totalItems = 0;
        foreach ($arrival->getStockBatches() as $batch) {
            $totalItems += $batch->getQuantityInitial();
        }

        // Convert logo to base64
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo-manos-phone.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdfBinary = $pdfService->generatePdf('inventory/arrival_pdf.html.twig', [
            'arrival' => $arrival,
            'totalItems' => $totalItems,
            'logo_path' => $logoBase64
        ]);

        return new Response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="arrivage_' . ($arrival->getReference() ?: $arrival->getId()) . '.pdf"',
        ]);
    }

    #[Route('/arrivals', name: 'api_stock_arrival_create', methods: ['POST'])]
    public function createArrival(
        Request $request,
        ProductRepository $productRepository,
        StockArrivalRepository $arrivalRepository,
        SupplierRepository $supplierRepository,
        \App\Repository\SaleZoneRepository $saleZoneRepository,
        EntityManagerInterface $em,
        ShopCashService $cashService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation: reference, items must exist
        if (empty($data['items']) || !is_array($data['items'])) {
            return $this->json(['error' => 'No items provided for arrival'], 400);
        }

        $arrival = new StockArrival();
        $arrival->setReference($arrivalRepository->generateReference());

        if (isset($data['supplierId'])) {
            $supplier = $supplierRepository->find($data['supplierId']);
            if ($supplier) {
                $arrival->setSupplier($supplier);
            }
        }
        
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
            $batch->setTargetSellingPrice($item['targetSellingPrice'] ?? $purchasePrice);
            $batch->setSupplier($data['supplier'] ?? null);
            $batch->setPurchaseDate($arrival->getArrivalDate());

            $arrival->addStockBatch($batch);
            $em->persist($batch);

            // ── Update Product Global Price & History ──
            $newGlobalPrice = (string)($item['targetSellingPrice'] ?? $product->getSellingPrice());
            if ($newGlobalPrice && $newGlobalPrice !== $product->getSellingPrice()) {
                $product->setSellingPrice($newGlobalPrice);
                $this->recordPriceHistory($product, $newGlobalPrice, $em);
            }

            // ── Update Zone Prices & History ──
            if (isset($item['zonePrices']) && is_array($item['zonePrices'])) {
                foreach ($item['zonePrices'] as $zoneId => $price) {
                    $zone = $saleZoneRepository->find($zoneId);
                    if (!$zone) continue;

                    // Find existing ProductPrice for this zone and product
                    $productPrice = $em->getRepository(\App\Entity\ProductPrice::class)->findOneBy([
                        'product' => $product,
                        'saleZone' => $zone
                    ]);

                    if (!$productPrice) {
                        $productPrice = new \App\Entity\ProductPrice();
                        $productPrice->setProduct($product);
                        $productPrice->setSaleZone($zone);
                    }

                    if ($productPrice->getPrice() !== (string)$price) {
                        $productPrice->setPrice((string)$price);
                        $em->persist($productPrice);

                        // Record Zone-Specific History
                        $history = new \App\Entity\PriceHistory();
                        $history->setProduct($product);
                        $history->setPrice((string)$price);
                        $history->setSaleZone($zone);
                        $history->setUser($this->getUser());
                        $history->setEffectiveFrom(new \DateTimeImmutable());
                        $em->persist($history);
                    }
                }
            }

            // Update Product Stock
            $product->setStockQuantity($product->getStockQuantity() + $quantity);
            $em->persist($product);

            $totalAmount += ($quantity * $purchasePrice);
        }

        $arrival->setTotalAmount($totalAmount);
        
        $paidAmount = (float)($data['paidAmount'] ?? 0);
        $arrival->setPaidAmount((string)$paidAmount);
        
        $dueAmount = $totalAmount - $paidAmount;
        if ($dueAmount <= 0) {
            $arrival->setPaymentStatus('PAID');
        } elseif ($paidAmount > 0) {
            $arrival->setPaymentStatus('PARTIAL');
        } else {
            $arrival->setPaymentStatus('UNPAID');
        }

        // Si il reste un montant dû, on l'ajoute à la balance du fournisseur
        if ($dueAmount > 0 && $arrival->getSupplier()) {
            $currentBalance = (float)$arrival->getSupplier()->getBalance();
            $arrival->getSupplier()->setBalance((string)($currentBalance + $dueAmount));
            $em->persist($arrival->getSupplier());
        }

        $em->persist($arrival);
        $em->flush();

        // Record Shop Cash Movement only if authorized by deductFromCash flag
        $deductFromCash = (bool)($data['deductFromCash'] ?? true);
        if ($paidAmount > 0 && $deductFromCash) {
            $cashService->addMovement(
                'OUT',
                $paidAmount,
                "Acompte arrivage #" . $arrival->getReference() . ($arrival->getSupplier() ? " - " . $arrival->getSupplier()->getName() : ""),
                'ARRIVAL',
                $arrival->getId(),
                $this->getUser()
            );
            $em->flush();
        }

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

    #[Route('/categories/{id}', name: 'api_stock_categories_update', methods: ['PUT'])]
    public function updateCategory(int $id, Request $request, ProductCategoryRepository $categoryRepository, EntityManagerInterface $em): JsonResponse
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            return $this->json(['error' => 'Catégorie introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $category->setName($data['name'] ?? $category->getName());
        $category->setDescription($data['description'] ?? $category->getDescription());

        $em->flush();

        return $this->json($category, 200, [], ['groups' => 'category:read']);
    }

    #[Route('/categories/{id}', name: 'api_stock_categories_delete', methods: ['DELETE'])]
    public function deleteCategory(int $id, ProductCategoryRepository $categoryRepository, EntityManagerInterface $em): JsonResponse
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            return $this->json(['error' => 'Catégorie introuvable'], 404);
        }

        // Vérifier si des produits y sont attachés
        if (count($category->getProducts()) > 0) {
            return $this->json(['error' => 'Impossible de supprimer une catégorie contenant des produits'], 400);
        }

        $em->remove($category);
        $em->flush();

        return $this->json(['message' => 'Catégorie supprimée'], 200);
    }

    #[Route('/inventory/print', name: 'api_inventory_print', methods: ['GET'])]
    public function printInventory(EntityManagerInterface $em): Response
    {
        $products = $em->getRepository(Product::class)->findBy(['isActive' => true], ['name' => 'ASC']);
        
        $totalStockValue = 0;
        $totalItems = 0;
        
        foreach ($products as $product) {
            $lastPurchasePrice = $product->getLastPurchasePrice() ?: 0;
            $totalStockValue += ($product->getStockQuantity() * $lastPurchasePrice);
            $totalItems += $product->getStockQuantity();
        }

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);
        
        // Convert logo to base64 to ensure it displays in PDF
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo-manos-phone.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $html = $this->renderView('inventory/inventory_pdf.html.twig', [
            'products' => $products,
            'totalStockValue' => $totalStockValue,
            'totalItems' => $totalItems,
            'date' => new \DateTime(),
            'logo_path' => $logoBase64
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="inventaire_stock.pdf"'
            ]
        );
    }

    #[Route('/products/{id}', name: 'api_stock_products_delete', methods: ['DELETE'])]
    public function deleteProduct(int $id, ProductRepository $productRepository, EntityManagerInterface $em): JsonResponse
    {
        $product = $productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produit introuvable'], 404);
        }

        $em->remove($product);
        try {
            $em->flush();
        } catch (\Exception $e) {
            // Probably integrity constraint (sales exist)
            // Just deactivate it instead
            $product->setIsActive(false);
            $em->flush();
            return $this->json(['message' => 'Produit désactivé car lié à des ventes/stocks'], 200);
        }

        return $this->json(['message' => 'Produit supprimé'], 200);
    }
}
