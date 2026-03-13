<?php

namespace App\Controller\Api;

use App\Entity\Ressource;
use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ressources', name: 'api_ressources_')]
class RessourceApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, RessourceRepository $ressourceRepository): JsonResponse
    {
        $filters = [
            'status' => 'validated',
            'author' => $request->query->get('author'),
            'type' => $request->query->get('type'),
            'category' => $request->query->get('category'),
            'relation' => $request->query->get('relation'),
        ];

        // Retire les filtres vides
        $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

        $ressources = $ressourceRepository->findByFilters($filters);
        
        $data = array_map([$this, 'formatRessource'], $ressources);

        return $this->json($data);
    }

    #[Route('/user/authored', name: 'authored', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function authored(RessourceRepository $ressourceRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $ressources = $ressourceRepository->findAuthoredByUser($user);
        
        $data = array_map([$this, 'formatRessource'], $ressources);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id, RessourceRepository $ressourceRepository): JsonResponse
    {
        try {
            $uuid = new \Symfony\Component\Uid\Uuid($id);
            $ressource = $ressourceRepository->find($uuid);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Format d\'ID invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$ressource) {
            return $this->json(['error' => 'Ressource non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($this->formatRessource($ressource));
    }

    private function formatRessource(Ressource $ressource): array
    {
        return [
            'id' => (string) $ressource->getId(),
            'title' => $ressource->getTitle(),
            'content' => $ressource->getContent(),
            'type' => $ressource->getType(),
            'creationDate' => $ressource->getCreationDate()?->format(\DateTime::ATOM),
            'status' => $ressource->getStatus(),
            'size' => $ressource->getSize(),
            'author' => $ressource->getAuthor() ? [
                'id' => (string) $ressource->getAuthor()->getId(),
                'email' => $ressource->getAuthor()->getEmail(),
            ] : null,
            'category' => $ressource->getCategory() ? [
                'id' => (string) $ressource->getCategory()->getId(),
                'name' => $ressource->getCategory()->getName(),
            ] : null,
            'relationTypes' => array_values($ressource->getRelationTypes()->map(function ($rt) {
                return [
                    'id' => (string) $rt->getId(),
                    'name' => $rt->getName(),
                ];
            })->toArray()),
            'likesCount' => $ressource->getLikedBy()->count(),
            'favoritesCount' => $ressource->getFavoritedBy()->count(),
            'savesCount' => $ressource->getSetAsideBy()->count(),
        ];
    }
}
