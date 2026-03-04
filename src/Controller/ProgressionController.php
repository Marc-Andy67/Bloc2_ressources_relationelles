<?php

namespace App\Controller;

use App\Repository\ProgressionRepository;
use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/progression')]
final class ProgressionController extends AbstractController
{
    #[Route(name: 'app_progression_index', methods: ['GET'])]
    public function index(ProgressionRepository $progressionRepository, RessourceRepository $ressourceRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Progressions récentes
        $progressions = $progressionRepository->findBy(['user' => $user], ['date' => 'DESC'], 10);

        // Ressources consultées (via Progression)
        $allProgressions = $progressionRepository->findBy(['user' => $user]);
        $resourcesRead = count(array_unique(array_map(
            fn($p) => $p->getRessource()?->getId(),
            $allProgressions
        )));

        // Statistiques d'interaction
        $favorites = $ressourceRepository->findFavoritedByUser($user);
        $saved = $ressourceRepository->findSetAsideByUser($user);
        $liked = $ressourceRepository->findLikedByUser($user);

        return $this->render('progression/dashboard.html.twig', [
            'progressions' => $progressions,
            'resources_read' => $resourcesRead,
            'favorites_count' => count($favorites),
            'saved_count' => count($saved),
            'liked_count' => count($liked),
        ]);
    }
}
