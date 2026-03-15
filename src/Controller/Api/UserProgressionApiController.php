<?php

namespace App\Controller\Api;

use App\Repository\ProgressionRepository;
use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user')]
class UserProgressionApiController extends AbstractController
{
    #[Route('/progression', name: 'api_user_progression', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function dashboard(
        RessourceRepository $ressourceRepository,
        ProgressionRepository $progressionRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Stats des interactions
        $liked = $ressourceRepository->findLikedByUser($user);
        $favorites = $ressourceRepository->findFavoritedByUser($user);
        $saved = $ressourceRepository->findSetAsideByUser($user);

        // Historique d'activité récent (30 derniers jours)
        $recentActivity = $progressionRepository->findRecentByUser($user, 30);

        return $this->json([
            'stats' => [
                'liked' => count($liked),
                'favorites' => count($favorites),
                'saved' => count($saved),
            ],
            'recentActivity' => array_map(function ($progression) {
                return [
                    'id' => (string) $progression->getId(),
                    'action' => $progression->getDescription(),
                    'date' => $progression->getDate()?->format(\DateTime::ATOM),
                    'ressource' => $progression->getRessource() ? [
                        'id' => (string) $progression->getRessource()->getId(),
                        'title' => $progression->getRessource()->getTitle(),
                    ] : null,
                ];
            }, $recentActivity),
        ]);
    }
}
