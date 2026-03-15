<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/comments', name: 'api_comment_moderation_')]
class CommentModerationApiController extends AbstractController
{
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(
        Comment $comment,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Suppression douce — anonymisation du contenu comme dans le web
        $comment->setContent('[Commentaire supprimé par la modération]');
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Commentaire anonymisé avec succès.',
            'id' => (string) $comment->getId(),
            'content' => $comment->getContent(),
        ]);
    }

    // Fallback pour les utilisateurs non modérateurs
    #[Route('/{id}', name: 'delete_forbidden', methods: ['DELETE'], priority: -1)]
    public function deleteForbidden(): JsonResponse
    {
        return $this->json(['error' => 'Accès refusé. Seul un modérateur peut supprimer un commentaire.'], JsonResponse::HTTP_FORBIDDEN);
    }
}
