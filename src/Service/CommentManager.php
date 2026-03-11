<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Ressource;
use App\Entity\User;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CommentRepository $commentRepository,
        private ProgressionService $progressionService
    ) {
    }

    public function createComment(
        Comment $comment,
        UserInterface $user,
        Ressource $ressource,
        ?string $parentId = null
    ): void {
        assert($user instanceof User);

        $comment->setAuthor($user);
        $comment->setCreationDate(new \DateTime());
        $comment->setRessource($ressource);

        if ($parentId) {
            $parentComment = $this->commentRepository->find($parentId);
            // Verify that the parent comment belongs to the same resource
            if ($parentComment && $parentComment->getRessource() === $ressource) {
                $comment->setParent($parentComment);
            }
        }

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        // Record the activity for progression
        $this->progressionService->recordActivity($user, $ressource, ProgressionService::ACTION_COMMENT);
    }
}
