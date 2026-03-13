<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Ressource;
use App\Repository\CommentRepository;
use App\Service\ProgressionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ressources/{id}/comments', name: 'api_comments_')]
class CommentApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Ressource $ressource, CommentRepository $commentRepository): JsonResponse
    {
        // Pour l'app on récupère les comm' parents (au top niveau)
        $comments = $commentRepository->findBy([
            'ressource' => $ressource,
            'parent' => null
        ], ['creationDate' => 'DESC']);

        $data = array_map([$this, 'formatComment'], $comments);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        Ressource $ressource,
        EntityManagerInterface $entityManager,
        ProgressionService $progressionService,
        CommentRepository $commentRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['content'])) {
            return $this->json(['error' => 'Le contenu du commentaire est requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setAuthor($user);
        $comment->setRessource($ressource);
        $comment->setCreationDate(new \DateTime());

        if (isset($data['parentId']) && !empty($data['parentId'])) {
            try {
                $parentUuid = new \Symfony\Component\Uid\Uuid($data['parentId']);
                $parentComment = $commentRepository->find($parentUuid);
                if ($parentComment && $parentComment->getRessource() === $ressource) {
                    $comment->setParent($parentComment);
                }
            } catch (\InvalidArgumentException $e) {
                // Ignore invalid parent UUID
            }
        }

        $entityManager->persist($comment);
        
        // Historique d'activité
        $progressionService->recordActivity($user, $ressource, ProgressionService::ACTION_COMMENT);
        
        $entityManager->flush();

        return $this->json($this->formatComment($comment), JsonResponse::HTTP_CREATED);
    }

    private function formatComment(Comment $comment): array
    {
        return [
            'id' => (string) $comment->getId(),
            'content' => $comment->getContent(),
            'creationDate' => $comment->getCreationDate()?->format(\DateTime::ATOM),
            'author' => [
                'id' => (string) $comment->getAuthor()->getId(),
                'name' => $comment->getAuthor()->getName() ?? clone $comment->getAuthor()->getUserIdentifier(),
            ],
            // On peut mapper les enfants récursivement si besoin
            'repliesCount' => $comment->getChildren()->count(),
            'replies' => array_values($comment->getChildren()->map(fn($child) => [
                'id' => (string) $child->getId(),
                'content' => $child->getContent(),
                'creationDate' => $child->getCreationDate()?->format(\DateTime::ATOM),
                'author' => [
                    'id' => (string) $child->getAuthor()->getId(),
                    'name' => $child->getAuthor()->getName() ?? clone $child->getAuthor()->getUserIdentifier(),
                ]
            ])->toArray())
        ];
    }
}
