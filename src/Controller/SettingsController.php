<?php

namespace App\Controller;

use App\Entity\ParametresCaisse;
use App\Repository\ParametresCaisseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/settings')]
class SettingsController extends AbstractController
{
    #[Route('/cash', name: 'api_settings_cash_get', methods: ['GET'])]
    public function getSettings(ParametresCaisseRepository $repository): JsonResponse
    {
        $settings = $repository->findOneBy([]);
        if (!$settings) {
            $settings = new ParametresCaisse();
        }

        return $this->json($settings, 200, [], ['groups' => ['session:read']]);
    }

    #[Route('/cash', name: 'api_settings_cash_post', methods: ['POST'])]
    public function updateSettings(
        Request $request,
        ParametresCaisseRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $settings = $repository->findOneBy([]);
        if (!$settings) {
            $settings = new ParametresCaisse();
            $em->persist($settings);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['heureRappelCloture'])) {
            $settings->setHeureRappelCloture(new \DateTime($data['heureRappelCloture']));
        }
        
        $settings->setFrequenceRappel($data['frequenceRappel'] ?? null);
        $settings->setBloquerOperationsSiNonCloture($data['bloquerOperationsSiNonCloture'] ?? false);

        $em->flush();

        return $this->json($settings, 200, [], ['groups' => ['session:read']]);
    }
}
