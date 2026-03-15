<?php

namespace App\Controller\Api;

use App\Entity\Ressource;
use App\Service\ProgressionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ressources', name: 'api_progression_')]
class ProgressionApiController extends AbstractController
{
    #[Route('/{id}/action', name: 'action', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleAction(
        Request $request,
        Ressource $ressource,
        ProgressionService $progressionService,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $actionRaw = $data['action'] ?? null;

        // Mapping des actions courtes Flutter vers les constantes métier
        $actionMap = [
            'like'     => ProgressionService::ACTION_LIKE,
            'favorite' => ProgressionService::ACTION_FAVORITE,
            'save'     => ProgressionService::ACTION_SAVE,
            'view'     => ProgressionService::ACTION_VIEW,
        ];

        $action = $actionMap[$actionRaw] ?? null;

        $validActions = [
            ProgressionService::ACTION_LIKE,
            ProgressionService::ACTION_FAVORITE,
            ProgressionService::ACTION_SAVE,
            ProgressionService::ACTION_VIEW
        ];

        if (!in_array($action, $validActions)) {
            return $this->json(['error' => 'Action invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $isActive = false;

        switch ($action) {
            case ProgressionService::ACTION_VIEW:
                // Juste historique
                $progressionService->recordActivity($user, $ressource, $action);
                return $this->json(['message' => 'Vue enregistrée avec succès.', 'action' => $action]);

            case ProgressionService::ACTION_LIKE:
                if ($ressource->getLikedBy()->contains($user)) {
                    $ressource->removeLikedBy($user);
                    $progressionService->recordActivity($user, $ressource, ProgressionService::ACTION_UNLIKE);
                } else {
                    $ressource->addLikedBy($user);
                    $isActive = true;
                    $progressionService->recordActivity($user, $ressource, $action);
                }
                break;

            case ProgressionService::ACTION_FAVORITE:
                if ($ressource->getFavoritedBy()->contains($user)) {
                    $ressource->removeFavoritedBy($user);
                    $progressionService->recordActivity($user, $ressource, ProgressionService::ACTION_UNFAVORITE);
                } else {
                    $ressource->addFavoritedBy($user);
                    $isActive = true;
                    $progressionService->recordActivity($user, $ressource, $action);
                }
                break;

            case ProgressionService::ACTION_SAVE:
                if ($ressource->getSetAsideBy()->contains($user)) {
                    $ressource->removeSetAsideBy($user);
                    $progressionService->recordActivity($user, $ressource, ProgressionService::ACTION_UNSAVE);
                } else {
                    $ressource->addSetAsideBy($user);
                    $isActive = true;
                    $progressionService->recordActivity($user, $ressource, $action);
                }
                break;
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Action effectuée avec succès.',
            'action' => $action,
            'isActive' => $isActive,
            'ressourceId' => (string) $ressource->getId()
        ]);
    }

    #[Route('/{id}/status', name: 'status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getStatus(Ressource $ressource): JsonResponse 
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            ProgressionService::ACTION_LIKE => $ressource->getLikedBy()->contains($user),
            ProgressionService::ACTION_FAVORITE => $ressource->getFavoritedBy()->contains($user),
            ProgressionService::ACTION_SAVE => $ressource->getSetAsideBy()->contains($user),
        ]);
    }

}
