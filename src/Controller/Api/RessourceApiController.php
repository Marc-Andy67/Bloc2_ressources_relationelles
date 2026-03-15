<?php

namespace App\Controller\Api;

use App\Entity\Ressource;
use App\Repository\RessourceRepository;
use App\Service\ProgressionService;
use App\Service\ChatRoomGenerator;
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
        $user = $this->getUser();
        
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
        
        // Ajoute les ressources pending de l'auteur connecté
        if ($user) {
            $pendingOwn = $ressourceRepository->findBy([
                'author' => $user,
                'status' => 'pending',
            ]);
            // Fusionne sans doublons
            $allIds = array_map(fn($r) => (string) $r->getId(), $ressources);
            foreach ($pendingOwn as $pending) {
                if (!in_array((string) $pending->getId(), $allIds)) {
                    $ressources[] = $pending;
                }
            }
        }
        
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

    #[Route('/admin/all', name: 'admin_all', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function adminAll(Request $request, RessourceRepository $ressourceRepository): JsonResponse
    {
        $status = $request->query->get('status');
        $criteria = [];
        if ($status) {
            $criteria['status'] = $status;
        }

        $ressources = $ressourceRepository->findBy($criteria, ['creationDate' => 'DESC']);
        $data = array_map([$this, 'formatRessource'], $ressources);
        return $this->json($data);
    }

    #[Route('/{id}/status', name: 'patch_status', methods: ['PATCH'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function patchStatus(
        Request $request,
        string $id,
        RessourceRepository $ressourceRepository,
        \Doctrine\ORM\EntityManagerInterface $em,
        ChatRoomGenerator $chatRoomGenerator
    ): JsonResponse {
        try {
            $uuid = new \Symfony\Component\Uid\Uuid($id);
            $ressource = $ressourceRepository->find($uuid);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Format d\'ID invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$ressource) {
            return $this->json(['error' => 'Ressource non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;
        if (!in_array($newStatus, ['validated', 'rejected', 'suspended'])) {
            return $this->json(['error' => 'Statut invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $ressource->setStatus($newStatus);
        $em->flush();

        if ($newStatus === 'validated') {
            $chatRoomGenerator->generateForJeu($ressource);
        }

        return $this->json($this->formatRessource($ressource));
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
        $ressource->setType($data['type'] ?? 'article');
        $ressource->setCreationDate(new \DateTime());
        
        // Status defaults to 'pending' unless user is admin/moderator
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles) || in_array('ROLE_MODERATOR', $roles) || in_array('ROLE_SUPER_ADMIN', $roles)) {
            $ressource->setStatus('validated');
        } else {
            $ressource->setStatus('pending');
        }

        $ressource->setAuthor($user);
        
        try {
            $categoryId = new \Symfony\Component\Uid\Uuid($data['category']);
            $category = $categoryRepository->find($categoryId);
        } catch (\InvalidArgumentException $e) {
            $category = null;
        }

        if (!$category) {
            return $this->json(
                ['error' => 'Catégorie introuvable avec l\'id : ' . $data['category']],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $ressource->setCategory($category);

        if (isset($data['relationTypes']) && is_array($data['relationTypes'])) {
            foreach ($data['relationTypes'] as $rtId) {
                try {
                    $rtUuid = new \Symfony\Component\Uid\Uuid($rtId);
                    $rt = $relationTypeRepository->find($rtUuid);
                    if ($rt) {
                        $ressource->addRelationType($rt);
                    }
                } catch (\InvalidArgumentException $e) {
                    // Ignore invalid UUID silently
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

    #[Route('/{id}', name: 'update', methods: ['PATCH', 'PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(
        string $id,
        Request $request,
        RessourceRepository $ressourceRepository,
        \App\Repository\CategoryRepository $categoryRepository,
        \App\Repository\RelationTypeRepository $relationTypeRepository,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $uuid = new \Symfony\Component\Uid\Uuid($id);
            $ressource = $ressourceRepository->find($uuid);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Format d\'ID invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$ressource) {
            return $this->json(['error' => 'Ressource non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est bien l'auteur
        if ($ressource->getAuthor() !== $user) {
            return $this->json(['error' => 'Vous n\'êtes pas l\'auteur de cette ressource'], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title']) && !empty($data['title'])) {
            $ressource->setTitle($data['title']);
        }
        if (isset($data['content']) && !empty($data['content'])) {
            $ressource->setContent($data['content']);
        }
        if (isset($data['category']) && !empty($data['category'])) {
            try {
                $category = $categoryRepository->find(new \Symfony\Component\Uid\Uuid($data['category']));
                if ($category) $ressource->setCategory($category);
            } catch (\InvalidArgumentException $e) {}
        }
        if (isset($data['relationTypes']) && is_array($data['relationTypes'])) {
            // Retire tous les types existants
            foreach ($ressource->getRelationTypes() as $rt) {
                $ressource->removeRelationType($rt);
            }
            // Ajoute les nouveaux
            foreach ($data['relationTypes'] as $rtId) {
                try {
                    $rt = $relationTypeRepository->find(new \Symfony\Component\Uid\Uuid($rtId));
                    if ($rt) $ressource->addRelationType($rt);
                } catch (\InvalidArgumentException $e) {}
            }
        }

        $entityManager->flush();
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
