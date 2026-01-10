<?php

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function request(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        \App\Service\MailerService $mailerService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['error' => 'Email est requis'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        // Pour des raisons de sécurité, on ne dit pas si l'utilisateur existe ou non
        if ($user) {
            // Supprimer les anciennes demandes
            $oldRequests = $entityManager->getRepository(ResetPasswordRequest::class)->findBy(['user' => $user]);
            foreach ($oldRequests as $oldRequest) {
                $entityManager->remove($oldRequest);
            }

            $token = bin2hex(random_bytes(32));
            $resetRequest = new ResetPasswordRequest(
                $user,
                $token,
                (new \DateTime())->modify('+1 hour')
            );

            $entityManager->persist($resetRequest);
            $entityManager->flush();

            try {
                $mailerService->sendResetPasswordEmail($user, $token);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'envoie de l\'email : ' . $e->getMessage()], 500);
            }
        }


        return new JsonResponse(['message' => 'Si cet e-mail correspond à un compte, un lien de réinitialisation a été envoyé.']);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function reset(
        Request $request,
        ResetPasswordRequestRepository $requestRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $password = $data['password'] ?? null;

        if (!$token || !$password) {
            return new JsonResponse(['error' => 'Token et mot de passe sont requis'], 400);
        }

        $resetRequest = $requestRepository->findOneBy(['token' => $token]);

        if (!$resetRequest || $resetRequest->isExpired()) {
            return new JsonResponse(['error' => 'Token invalide ou expiré'], 400);
        }

        $user = $resetRequest->getUser();
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $entityManager->remove($resetRequest);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Votre mot de passe a été réinitialisé avec succès.']);
    }
}
