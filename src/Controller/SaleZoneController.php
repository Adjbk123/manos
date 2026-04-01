<?php

namespace App\Controller;

use App\Entity\SaleZone;
use App\Entity\ProductPrice;
use App\Entity\PriceHistory;
use App\Repository\SaleZoneRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductPriceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/sale-zones')]
class SaleZoneController extends AbstractController
{
    #[Route('', name: 'api_sale_zones_list', methods: ['GET'])]
    public function list(SaleZoneRepository $repository): JsonResponse
    {
        return $this->json($repository->findAll(), 200, [], ['groups' => 'sale_zone:read']);
    }

    #[Route('', name: 'api_sale_zones_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $zone = new SaleZone();
        $zone->setName($data['name']);
        $zone->setDescription($data['description'] ?? null);
        $zone->setIsActive($data['isActive'] ?? true);

        $em->persist($zone);
        $em->flush();

        return $this->json($zone, 201, [], ['groups' => 'sale_zone:read']);
    }

    #[Route('/{id}', name: 'api_sale_zones_update', methods: ['PUT'])]
    public function update(int $id, Request $request, SaleZoneRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $zone = $repository->find($id);
        if (!$zone) return $this->json(['error' => 'Zone not found'], 404);

        $data = json_decode($request->getContent(), true);
        $zone->setName($data['name'] ?? $zone->getName());
        $zone->setDescription($data['description'] ?? $zone->getDescription());
        if (isset($data['isActive'])) $zone->setIsActive($data['isActive']);

        $em->flush();

        return $this->json($zone, 200, [], ['groups' => 'sale_zone:read']);
    }

    #[Route('/{id}', name: 'api_sale_zones_delete', methods: ['DELETE'])]
    public function delete(int $id, SaleZoneRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $zone = $repository->find($id);
        if (!$zone) return $this->json(['error' => 'Zone not found'], 404);

        $em->remove($zone);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/product-prices', name: 'api_sale_zones_update_prices', methods: ['POST'])]
    public function updateProductPrices(Request $request, ProductRepository $productRepository, SaleZoneRepository $zoneRepository, ProductPriceRepository $priceRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        // data: { productId: x, prices: { zoneId: price, ... } }
        
        $product = $productRepository->find($data['productId']);
        if (!$product) return $this->json(['error' => 'Product not found'], 404);

        foreach ($data['prices'] as $zoneId => $priceVal) {
            $zone = $zoneRepository->find($zoneId);
            if (!$zone) continue;

            $productPrice = $priceRepository->findOneBy(['product' => $product, 'saleZone' => $zone]);
            $oldPrice = $productPrice ? $productPrice->getPrice() : null;
            $newPrice = ($priceVal === null || $priceVal === '') ? null : (string)$priceVal;

            if ($newPrice === null) {
                if ($productPrice) {
                    $em->remove($productPrice);
                    $this->recordPriceHistory($product, $zone, null, $em);
                }
            } else {
                if ($oldPrice !== $newPrice) {
                    if (!$productPrice) {
                        $productPrice = new ProductPrice();
                        $productPrice->setProduct($product);
                        $productPrice->setSaleZone($zone);
                    }
                    $productPrice->setPrice($newPrice);
                    $em->persist($productPrice);

                    // Record History
                    $this->recordPriceHistory($product, $zone, $newPrice, $em);
                }
            }
        }

        $em->flush();

        return $this->json(['success' => true]);
    }

    private function recordPriceHistory($product, $zone, $price, $em)
    {
        $history = new PriceHistory();
        $history->setProduct($product);
        $history->setSaleZone($zone);
        $history->setPrice($price ?? '0'); // Fallback to '0' strings for history if deleted
        $history->setUser($this->getUser());
        $em->persist($history);
    }
}
