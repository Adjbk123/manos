<?php

namespace App\Controller;

use App\Entity\Sale;
use App\Entity\SaleItem;
use App\Entity\StockClient;
use App\Entity\SaleZone;
use App\Entity\CreditPayment;
use App\Repository\ProductRepository;
use App\Repository\SaleRepository;
use App\Repository\StockBatchRepository;
use App\Repository\StockClientRepository;
use App\Repository\UserRepository;
use App\Repository\SupplierRepository;
use App\Repository\ShopCashMovementRepository;
use App\Repository\StockAdjustmentRepository;
use App\Repository\SaleZoneRepository;
use App\Service\ShopCashService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/pos')]
class PosController extends AbstractController
{
    #[Route('/products', name: 'api_pos_products_list', methods: ['GET'])]
    public function listProducts(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $filters = $request->query->all();
        $products = $productRepository->findByFilters($filters);
        return $this->json($products, 200, [], ['groups' => 'stock:read']);
    }

    #[Route('/clients', name: 'api_pos_clients_list', methods: ['GET'])]
    public function listClients(StockClientRepository $clientRepository): JsonResponse
    {
        return $this->json($clientRepository->findAll(), 200, [], ['groups' => 'stock_client:read']);
    }

    #[Route('/clients/{id}', name: 'api_pos_clients_detail', methods: ['GET'])]
    public function getClientDetail(int $id, StockClientRepository $clientRepository): JsonResponse
    {
        $client = $clientRepository->find($id);

        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        return $this->json($client, 200, [], [
            'groups' => 'stock_client:read',
            'enable_max_depth' => true
        ]);
    }

    #[Route('/clients', name: 'api_pos_clients_create', methods: ['POST'])]
    public function createClient(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $client = new StockClient();
        $client->setName($data['name']);
        $client->setPhones($data['phones'] ?? []);
        $client->setAddress($data['address'] ?? null);

        $em->persist($client);
        $em->flush();

        return $this->json($client, 201, [], ['groups' => 'stock_client:read']);
    }


    #[Route('/sales', name: 'api_pos_sales_create', methods: ['POST'])]
    public function createSale(
        Request $request,
        EntityManagerInterface $em,
        StockBatchRepository $batchRepository,
        StockClientRepository $clientRepository,
        ProductRepository $productRepository,
        UserRepository $userRepository,
        ShopCashService $cashService,
        SaleZoneRepository $zoneRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Transaction handling could be added here
        $em->beginTransaction();
        try {
            $sale = new Sale();
            $sale->setDate(new \DateTime());

            // Link User (assuming passed in body or extracted from token in real app)
            // For now, we might need to get the logged in user. 
            // In a stateless API context, usually $this->getUser() works if JWT is set up.
            // If not available, we might assume ID 1 or passed ID for proto.
            $user = $this->getUser();
            if (!$user && isset($data['userId'])) {
                $user = $userRepository->find($data['userId']);
            }
            if (!$user) {
                // Fallback or error in production
                // throw new \Exception('User required');
            }
            $sale->setUser($user);

            // Client handling
            if (isset($data['clientId'])) {
                $client = $clientRepository->find($data['clientId']);
                $sale->setStockClient($client);
                $sale->setClientName($client->getName());
            } else {
                $sale->setClientName($data['clientName'] ?? 'Client de passage');
            }

            $sale->setPaymentMethod($data['paymentMethod']); // CASH, MOMO
            $sale->setPaymentStatus($data['paymentStatus']); // PAID, PARTIAL, UNPAID

            // Sale Zone
            if (isset($data['saleZoneId'])) {
                $zone = $zoneRepository->find($data['saleZoneId']);
                if ($zone) {
                    $sale->setSaleZone($zone);
                }
            }

            $totalAmount = 0;

            foreach ($data['items'] as $itemData) {
                // Here is the FIFO logic or Batch selection logic
                // The frontend might send specific batchId if they selected a specific unit
                // OR they send productId and we determine the batch (FIFO)

                $productId = $itemData['productId'];
                $quantityRequested = $itemData['quantity'];
                $unitSellingPrice = $itemData['unitPrice'];
                $itemZoneId = $itemData['saleZoneId'] ?? null;
                $itemZone = $itemZoneId ? $zoneRepository->find($itemZoneId) : null;

                $product = $productRepository->find($productId);

                // FIFO Strategy: Find batches with stock ordered by date
                $batches = $batchRepository->createQueryBuilder('b')
                    ->where('b.product = :product')
                    ->andWhere('b.quantityRemaining > 0')
                    ->orderBy('b.purchaseDate', 'ASC')
                    ->setParameter('product', $product)
                    ->getQuery()
                    ->getResult();

                $quantityToFulfill = $quantityRequested;

                foreach ($batches as $batch) {
                    if ($quantityToFulfill <= 0)
                        break;

                    $qtyInBatch = $batch->getQuantityRemaining();
                    $take = min($qtyInBatch, $quantityToFulfill);

                    // Create SaleItem
                    $saleItem = new SaleItem();
                    $saleItem->setSale($sale);
                    $saleItem->setStockBatch($batch);
                    $saleItem->setQuantity($take);
                    $saleItem->setUnitSellingPrice($unitSellingPrice); // Provided price
                    $saleItem->setUnitPurchasePrice($batch->getPurchasePrice()); // Snapshot cost
                    $saleItem->setSaleZone($itemZone); // Per item zone

                    // Calculate profit: (Selling - Purchase) * Qty
                    $profit = ($unitSellingPrice - $batch->getPurchasePrice()) * $take;
                    $saleItem->setProfit($profit);

                    $em->persist($saleItem);

                    // Update Batch
                    $batch->setQuantityRemaining($qtyInBatch - $take);
                    $em->persist($batch);

                    $quantityToFulfill -= $take;
                }

                if ($quantityToFulfill > 0) {
                    throw new \Exception("Stock insuffisant pour le produit: " . $product->getName());
                }

                // Update Product Global Stock
                $product->setStockQuantity($product->getStockQuantity() - $quantityRequested);
                $em->persist($product);

                $totalAmount += ($unitSellingPrice * $quantityRequested);
            }

            $sale->setTotalAmount($totalAmount);
            $sale->setPaidAmount($data['paidAmount']);

            // Record the initial payment in CreditPayment for tracking
            if ($data['paidAmount'] > 0) {
                $payment = new CreditPayment();
                $payment->setSale($sale);
                $payment->setAmount($data['paidAmount']);
                $payment->setDate(new \DateTime());
                $payment->setPaymentMethod($data['paymentMethod']);
                if ($user)
                    $payment->setUser($user);
                if ($sale->getStockClient()) {
                    $payment->setClient($sale->getStockClient());
                }
                $em->persist($payment);
            }

            // Update Client Debt if necessary
            if ($sale->getStockClient()) {
                $debt = $totalAmount - $data['paidAmount'];
                if ($debt > 0) {
                    $client = $sale->getStockClient();
                    $client->setCurrentDebt($client->getCurrentDebt() + $debt);
                    $em->persist($client);
                }
            }

            $em->persist($sale);
            $em->flush();

            // Record Shop Cash Movement if CASH
            if ($sale->getPaymentMethod() === 'CASH' && $sale->getPaidAmount() > 0) {
                $cashService->addMovement(
                    'IN',
                    (float)$sale->getPaidAmount(),
                    "Vente #" . $sale->getId() . ($sale->getClientName() ? " - " . $sale->getClientName() : ""),
                    'SALE',
                    $sale->getId(),
                    $user
                );
                $em->flush();
            }

            $em->commit();

            return $this->json($sale, 201, [], ['groups' => 'sale:read']);

        } catch (\Exception $e) {
            $em->rollback();
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/sales', name: 'api_pos_sales_list', methods: ['GET'])]
    public function listSales(SaleRepository $saleRepository): JsonResponse
    {
        $sales = $saleRepository->findBy([], ['date' => 'DESC']);
        return $this->json($sales, 200, [], ['groups' => 'sale:read']);
    }

    #[Route('/sales/{id}', name: 'api_pos_sale_detail', methods: ['GET'])]
    public function getSale(int $id, SaleRepository $saleRepository): JsonResponse
    {
        $sale = $saleRepository->find($id);
        if (!$sale) {
            return $this->json(['error' => 'Vente introuvable'], 404);
        }
        return $this->json($sale, 200, [], [
            'groups' => ['sale:read', 'stock:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
            'enable_max_depth' => true,
        ]);
    }

    #[Route('/clients/{id}/pay', name: 'api_pos_clients_pay', methods: ['POST'])]
    public function payDebt(
        int $id,
        Request $request,
        StockClientRepository $clientRepository,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        ShopCashService $cashService
    ): JsonResponse {
        $client = $clientRepository->find($id);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $amount = $data['amount'];

        // Link user
        $user = $this->getUser();
        if (!$user) {
            $user = $userRepository->find(1) ?? $userRepository->findOneBy([]);
        }

        if (!$user) {
            return $this->json(['error' => 'Aucun utilisateur trouvé pour enregistrer le paiement'], 500);
        }

        if ($amount <= 0) {
            return $this->json(['error' => 'Invalid amount'], 400);
        }

        if ($amount > $client->getCurrentDebt()) {
            return $this->json(['error' => 'Amount exceeds debt'], 400);
        }

        $paymentMethod = $data['paymentMethod'] ?? 'CASH';

        $unpaidSales = $em->getRepository(Sale::class)->createQueryBuilder('s')
            ->where('s.stockClient = :client')
            ->andWhere('s.paymentStatus != :paid')
            ->setParameter('client', $client)
            ->setParameter('paid', 'PAID')
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();

        $remainingPayment = $amount;

        foreach ($unpaidSales as $sale) {
            if ($remainingPayment <= 0)
                break;

            $total = $sale->getTotalAmount();
            $paid = $sale->getPaidAmount();
            $due = $total - $paid;

            $pay = min($remainingPayment, $due);

            $sale->setPaidAmount($paid + $pay);
            if ($sale->getPaidAmount() >= $total) {
                $sale->setPaymentStatus('PAID');
            } else {
                $sale->setPaymentStatus('PARTIAL');
            }

            $em->persist($sale);

            // Create a CreditPayment record for this sale
            $subPayment = new CreditPayment();
            $subPayment->setSale($sale);
            $subPayment->setClient($client);
            $subPayment->setAmount($pay);
            $subPayment->setDate(new \DateTime());
            $subPayment->setPaymentMethod($paymentMethod);
            if ($user)
                $subPayment->setUser($user);
            $em->persist($subPayment);

            $remainingPayment -= $pay;
        }

        $client->setCurrentDebt($client->getCurrentDebt() - $amount);
        $em->persist($client);

        // Record Shop Cash Movement if CASH
        if ($paymentMethod === 'CASH') {
            $cashService->addMovement(
                'IN',
                (float)$amount,
                "Remboursement dette client : " . $client->getName(),
                'DEBT_REPAYMENT',
                null,
                $user
            );
        }

        $em->flush();

        return $this->json($client, 200, [], ['groups' => 'stock_client:read']);
    }

    #[Route('/products/{id}', name: 'api_pos_product_detail', methods: ['GET'])]
    public function getProduct(
        int $id,
        ProductRepository $productRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $product = $productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produit introuvable'], 404);
        }

        // Fetch sales for this product
        $saleItems = $em->getRepository(SaleItem::class)->createQueryBuilder('si')
            ->join('si.stockBatch', 'sb')
            ->where('sb.product = :product')
            ->setParameter('product', $product)
            ->orderBy('si.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->json([
            'product' => $product,
            'saleItems' => $saleItems,
        ], 200, [], [
            'groups' => ['product:read', 'stock:read', 'product_detail:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
            'enable_max_depth' => true,
        ]);
    }

    #[Route('/stats', name: 'api_pos_stats', methods: ['GET'])]
    public function getStats(
        Request $request,
        SaleRepository $saleRepository,
        SupplierRepository $supplierRepository,
        ShopCashMovementRepository $shopCashRepository,
        StockAdjustmentRepository $adjustmentRepository,
        StockClientRepository $clientRepository
    ): JsonResponse {
        $startDate = $request->query->get('start') ? new \DateTime($request->query->get('start')) : new \DateTime('first day of this month 00:00:00');
        $endDate = $request->query->get('end') ? new \DateTime($request->query->get('end')) : new \DateTime('now');
        $endDate->setTime(23, 59, 59);

        $salesStats = $saleRepository->getStats($startDate, $endDate);
        
        $supplierDebt = (float)$supplierRepository->createQueryBuilder('s')
            ->select('SUM(s.balance)')
            ->getQuery()
            ->getSingleScalarResult();

        $clientDebt = (float)$clientRepository->createQueryBuilder('c')
            ->select('SUM(c.currentDebt)')
            ->getQuery()
            ->getSingleScalarResult();

        $shopCashBalance = $shopCashRepository->getBalance();

        $lossesValue = (float)$adjustmentRepository->createQueryBuilder('a')
            ->join('a.batch', 'b')
            ->select('SUM(a.quantity * b.purchasePrice)')
            ->where('a.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'sales' => $salesStats['total_sales'],
            'profit' => $salesStats['total_profit'],
            'supplier_debt' => $supplierDebt,
            'client_debt' => $clientDebt,
            'shop_cash_balance' => $shopCashBalance,
            'losses_value' => $lossesValue,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ]
        ]);
    }
}
