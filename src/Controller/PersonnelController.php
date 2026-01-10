<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\AgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/personnel', name: 'api_personnel_')]
class PersonnelController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $role = $request->query->get('role');
        $users = $userRepository->findAll();

        if ($role) {
            $users = array_values(array_filter($users, function($user) use ($role) {
                $userRoles = $user->getRoles();
                if ($role === 'ROLE_AGENT') {
                    return in_array('ROLE_AGENT', $userRoles) || in_array('ROLE_ADMIN', $userRoles) || in_array('ROLE_SUPER_ADMIN', $userRoles);
                }
                return in_array($role, $userRoles);
            }));
        }
        
        return new JsonResponse(
            json_decode($serializer->serialize($users, 'json', ['groups' => 'user:read']))
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        AgenceRepository $agenceRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'JSON invalide'], 400);
        }

        $user = new User();
        $user->setNom($data['nom'] ?? null);
        $user->setEmail($data['email'] ?? null);
        $user->setTelephone($data['telephone'] ?? null);
        $user->setRoles($data['roles'] ?? ['ROLE_AGENT']);
        $user->setActif($data['actif'] ?? true);

        $agence = $agenceRepository->findOneBy([]);
        if ($agence) {
            $user->setAgence($agence);
        }

        if (empty($data['password'])) {
            return new JsonResponse(['error' => 'Le mot de passe est requis'], 400);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], 400);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(
            json_decode($serializer->serialize($user, 'json', ['groups' => 'user:read'])),
            201
        );
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) $user->setNom($data['nom']);
        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['telephone'])) $user->setTelephone($data['telephone']);
        if (isset($data['roles'])) $user->setRoles($data['roles']);
        if (isset($data['actif'])) $user->setActif($data['actif']);
        
        if (!empty($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $entityManager->flush();

        return new JsonResponse(
            json_decode($serializer->serialize($user, 'json', ['groups' => 'user:read']))
        );
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Empêcher de se supprimer soi-même
        if ($user === $this->getUser()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas supprimer votre propre compte'], 403);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur supprimé avec succès']);
    }
}
