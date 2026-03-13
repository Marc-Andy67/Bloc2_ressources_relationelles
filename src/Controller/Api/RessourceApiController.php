<?php

namespace App\Controller\Api;

use App\Entity\Ressource;
use App\Repository\RessourceRepository;
use App\Service\ProgressionService;
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

    #[Route('/user/favorites', name: 'favorites', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function favorites(RessourceRepository $ressourceRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $ressources = $ressourceRepository->findFavoritedByUser($user);
        $data = array_map([$this, 'formatRessource'], $ressources);
        return $this->json($data);
    }

    #[Route('/user/saved', name: 'saved', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function saved(RessourceRepository $ressourceRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $ressources = $ressourceRepository->findSetAsideByUser($user);
        $data = array_map([$this, 'formatRessource'], $ressources);
        return $this->json($data);
    }

    #[Route('/user/liked', name: 'liked', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function liked(RessourceRepository $ressourceRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $ressources = $ressourceRepository->findLikedByUser($user);
        $data = array_map([$this, 'formatRessource'], $ressources);
        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request, 
        \App\Repository\CategoryRepository $categoryRepository,
        \App\Repository\RelationTypeRepository $relationTypeRepository,
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        \App\Service\ProgressionService $progressionService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['title']) || empty($data['content']) || empty($data['category'])) {
            return $this->json(['error' => 'Titre, contenu et catégorie sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $ressource = new Ressource();
        $ressource->setTitle($data['title']);
        $ressource->setContent($data['content']);
        $ressource->setCreationDate(new \DateTime());
        
        // Status defaults to 'pending' unless user is admin/moderator
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles) || in_array('ROLE_MODERATOR', $roles) || in_array('ROLE_SUPER_ADMIN', $roles)) {
            $ressource->setStatus('validated');
        } else {
            $ressource->setStatus('pending');
        }

        $ressource->setAuthor($user);
        
        $category = $categoryRepository->find($data['category']);
        if ($category) {
            $ressource->setCategory($category);
        }

        if (isset($data['relationTypes']) && is_array($data['relationTypes'])) {
            foreach ($data['relationTypes'] as $rtId) {
                $rt = $relationTypeRepository->find($rtId);
                if ($rt) {
                    $ressource->addRelationType($rt);
                }
            }
        }

        $entityManager->persist($ressource);
        $progressionService->recordActivity($user, $ressource, ProgressionService::ACTION_CREATE_RESSOURCE);
        $entityManager->flush();

        return $this->json($this->formatRessource($ressource), JsonResponse::HTTP_CREATED);
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
                'name' => $ressource->getAuthor()->getName(),
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
