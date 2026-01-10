<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        \App\Repository\AgenceRepository $agenceRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        \Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'JSON invalide'], 400);
        }


        $user = new User();
        $user->setEmail($data['email'] ?? null);
        $user->setTelephone($data['telephone'] ?? null);
        $user->setNom($data['nom'] ?? null);
        
        // Attribution automatique à l'agence principale (création auto si nécessaire)
        $agence = $agenceRepository->findOrCreateMainAgence();
        $user->setAgence($agence);
        $user->setActif(true);


        // Validation simple
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], 400);
        }

        if (empty($data['password'])) {
            return new JsonResponse(['error' => 'Le mot de passe est requis'], 400);
        }


        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_AGENT']);


        $entityManager->persist($user);
        $entityManager->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'message' => 'Utilisateur inscrit avec succès',
            'access_token' => $token,
            'user' => json_decode($serializer->serialize($user, 'json', ['groups' => 'user:read']))
        ], 201);

    }


    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        \App\Repository\AgenceRepository $agenceRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data || (empty($data['email']) && empty($data['telephone'])) || empty($data['password'])) {
            return new JsonResponse(['error' => 'Email/Téléphone et mot de passe sont requis'], 400);
        }


        $user = null;
        if (!empty($data['email'])) {
            $user = $userRepository->findOneBy(['email' => $data['email']]);
        } elseif (!empty($data['telephone'])) {
            $user = $userRepository->findOneBy(['telephone' => $data['telephone']]);
        }

        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Identifiants invalides'], 401);
        }


        if (!$user->isActif()) {
            return new JsonResponse(['error' => 'Votre compte est désactivé. Veuillez contacter l\'administrateur.'], 401);
        }

        // Sécurité: s'assurer que l'utilisateur est lié à une agence valide
        try {
            if (!$user->getAgence() || $user->getAgence()->getNom() === null) {
                $user->setAgence($agenceRepository->findOrCreateMainAgence());
                $entityManager->flush();
            }
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $user->setAgence($agenceRepository->findOrCreateMainAgence());
            $entityManager->flush();
        }

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'access_token' => $token,
            'user' => json_decode($serializer->serialize($user, 'json', ['groups' => 'user:read']))
        ]);

    }

    #[Route('/user', name: 'me', methods: ['GET'])]
    public function me(
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        \App\Repository\AgenceRepository $agenceRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        // Sécurité: s'assurer que l'utilisateur est lié à une agence valide
        try {
            if (!$user->getAgence() || $user->getAgence()->getNom() === null) {
                $user->setAgence($agenceRepository->findOrCreateMainAgence());
                $entityManager->flush();
            }
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $user->setAgence($agenceRepository->findOrCreateMainAgence());
            $entityManager->flush();
        }

        return new JsonResponse(
            json_decode($serializer->serialize($user, 'json', ['groups' => 'user:read']))
        );
    }

    #[Route('/user/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Vérification que l'utilisateur modifie bien son propre profil (ou est admin)
        if ($user !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['telephone'])) {
            $user->setTelephone($data['telephone']);
        }

        $entityManager->flush();

        return new JsonResponse(
            json_decode($serializer->serialize($user, 'json', ['groups' => 'user:read']))
        );
    }
}

