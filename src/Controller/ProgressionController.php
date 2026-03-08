<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Repository\ProgressionRepository;
use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/progression')]
final class ProgressionController extends AbstractController
{
    #[Route(name: 'app_progression_index', methods: ['GET'])]
    public function index(ProgressionRepository $progressionRepository, RessourceRepository $ressourceRepository, CommentRepository $commentRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Progressions récentes (historique étendu)
        $progressions = $progressionRepository->findBy(['user' => $user], ['date' => 'DESC'], 50);

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

        // Nouvelles statistiques de création
        $published = $ressourceRepository->findAuthoredByUser($user);
        $comments = $commentRepository->findBy(['author' => $user]);

        return $this->render('progression/dashboard.html.twig', [
            'progressions' => $progressions,
            'resources_read' => $resourcesRead,
            'favorites_count' => count($favorites),
            'saved_count' => count($saved),
            'liked_count' => count($liked),
            'published_count' => count($published),
            'comment_count' => count($comments),
        ]);
    }
}
