<?php

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user', name: 'api_user_')]
class UserApiController extends AbstractController
{
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
            'lastConnection' => $user->getLastConnection()?->format('c'),
        ];

        return $this->json($data);
    }

    #[Route('/profile', name: 'update_profile', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Format JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $updated = false;

        if (isset($data['name']) && !empty($data['name'])) {
            $user->setName($data['name']);
            $updated = true;
        }

        if (isset($data['email']) && !empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            // Note : idéalement, vous pourriez vouloir vérifier l'unicité ici si cela change.
            $user->setEmail($data['email']);
            $updated = true;
        }

        if ($updated) {
            $entityManager->flush();
            return $this->json([
                'message' => 'Profil mis à jour avec succès.',
                'user' => [
                    'id' => (string) $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName()
                ]
            ]);
        }

        return $this->json(['message' => 'Aucune modification apportée.'], JsonResponse::HTTP_OK);
    }
}
