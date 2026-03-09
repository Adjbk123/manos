<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated Session management is being removed/refactored.
 */
#[Route('/api/sessions')]
class SessionController extends AbstractController
{
    #[Route('/status', name: 'api_sessions_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json([
            'isActive' => true,
            'isExpired' => false,
            'session' => null,
            'message' => 'Session system disabled'
        ]);
    }
}
