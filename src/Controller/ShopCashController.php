<?php

namespace App\Controller;

use App\Repository\ShopCashMovementRepository;
use App\Service\ShopCashService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/shop-cash')]
class ShopCashController extends AbstractController
{
    #[Route('/movements', name: 'api_shop_cash_movements', methods: ['GET'])]
    public function movements(ShopCashMovementRepository $repository): JsonResponse
    {
        $movements = $repository->findBy([], ['date' => 'DESC']);
        $balance = $repository->getBalance();

        return $this->json([
            'movements' => $movements,
            'balance' => $balance
        ], 200, [], ['groups' => 'shop_cash:read']);
    }

    #[Route('/manual', name: 'api_shop_cash_manual', methods: ['POST'])]
    public function addManual(Request $request, ShopCashService $cashService, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $movement = $cashService->addMovement(
            $data['type'],
            (float)$data['amount'],
            $data['label'],
            'MANUAL',
            null,
            $this->getUser()
        );

        $em->flush();

        return $this->json($movement, 201, [], ['groups' => 'shop_cash:read']);
    }
}
