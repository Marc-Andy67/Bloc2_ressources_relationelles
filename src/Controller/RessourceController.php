<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Form\RessourceType;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ressource')]
final class RessourceController extends AbstractController
{
    #[Route(name: 'app_ressource_index', methods: ['GET'])]
    public function index(
        Request $request,
        RessourceRepository $ressourceRepository,
        \App\Repository\CategoryRepository $categoryRepository,
        \App\Repository\RelationTypeRepository $relationTypeRepository
    ): Response {
        $filters = $request->query->all();
        $ressources = $ressourceRepository->findByFilters($filters);

        // Récupérer les types uniques pour le filtre "Type de ressource"
        $types = $ressourceRepository->createQueryBuilder('r')
            ->select('DISTINCT r.type')
            ->where('r.type IS NOT NULL')
            ->getQuery()
            ->getScalarResult();

        $typesList = array_map(fn($t) => $t['type'], $types);

        return $this->render('ressource/index.html.twig', [
            'ressources' => $ressources,
            'categories' => $categoryRepository->findAll(),
            'relationTypes' => $relationTypeRepository->findAll(),
            'types' => $typesList,
            'currentFilters' => $filters,
        ]);
    }

    #[Route('/favorites', name: 'app_ressource_favorites', methods: ['GET'])]
    public function favorites(RessourceRepository $ressourceRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('ressource/favorites.html.twig', [
            'ressources' => $ressourceRepository->findFavoritedByUser($user),
        ]);
    }

    #[Route('/saved', name: 'app_ressource_saved', methods: ['GET'])]
    public function saved(RessourceRepository $ressourceRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('ressource/saved.html.twig', [
            'ressources' => $ressourceRepository->findSetAsideByUser($user),
        ]);
    }

    #[Route('/liked', name: 'app_ressource_liked', methods: ['GET'])]
    public function liked(RessourceRepository $ressourceRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('ressource/liked.html.twig', [
            'ressources' => $ressourceRepository->findLikedByUser($user),
        ]);
    }

    // ─── Toggle Actions (AJAX) ────────────────────────────────────────────────

    #[Route('/{id}/toggle-favorite', name: 'app_ressource_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(Ressource $ressource, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $collection = $ressource->getFavoritedBy();
        $active = $collection->contains($user);
        if ($active) {
            $ressource->removeFavoritedBy($user);
        } else {
            $ressource->addFavoritedBy($user);
        }
        $em->flush();

        return $this->json(['active' => !$active, 'count' => $collection->count()]);
    }

    #[Route('/{id}/toggle-save', name: 'app_ressource_toggle_save', methods: ['POST'])]
    public function toggleSave(Ressource $ressource, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $collection = $ressource->getSetAsideBy();
        $active = $collection->contains($user);
        if ($active) {
            $ressource->removeSetAsideBy($user);
        } else {
            $ressource->addSetAsideBy($user);
        }
        $em->flush();

        return $this->json(['active' => !$active, 'count' => $collection->count()]);
    }

    #[Route('/{id}/toggle-like', name: 'app_ressource_toggle_like', methods: ['POST'])]
    public function toggleLike(Ressource $ressource, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $collection = $ressource->getLikedBy();
        $active = $collection->contains($user);
        if ($active) {
            $ressource->removeLikedBy($user);
        } else {
            $ressource->addLikedBy($user);
        }
        $em->flush();

        return $this->json(['active' => !$active, 'count' => $collection->count()]);
    }

    // ─── CRUD ────────────────────────────────────────────────────────────────

    #[Route('/new', name: 'app_ressource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, \App\Service\RessourceManagerInterface $ressourceManager, EntityManagerInterface $entityManager): Response
    {
        $dto = new \App\DTO\RessourceDTO();
        $form = $this->createForm(RessourceType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Lógica métiers déplacée dans le RessourceManager (Service Pattern)
            $ressource = $ressourceManager->createFromDTO($dto);

            // Doctrine's EventListener will automatically set Author, Status, and CreationDate (Observer Pattern)
            $entityManager->persist($ressource);
            $entityManager->flush();

            return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ressource/new.html.twig', [
            'ressource' => null, // Form now expects DTO context, twig might need slight adjustment if it explicitly relied on ressource.id
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ressource_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Ressource $ressource, EntityManagerInterface $entityManager, \App\Repository\CommentRepository $commentRepository): Response
    {
        $comment = new \App\Entity\Comment();
        $form = $this->createForm(\App\Form\CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->getUser()) {
            $comment->setAuthor($this->getUser());
            $comment->setCreationDate(new \DateTime());
            $comment->setRessource($ressource);

            $parentId = $request->request->get('parent_id');
            if ($parentId) {
                $parentComment = $commentRepository->find($parentId);
                if ($parentComment && $parentComment->getRessource() === $ressource) {
                    $comment->setParent($parentComment);
                }
            }

            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_ressource_show', ['id' => $ressource->getId()]);
        }

        $comments = $commentRepository->findBy(['ressource' => $ressource, 'parent' => null], ['creationDate' => 'DESC']);

        return $this->render('ressource/show.html.twig', [
            'ressource' => $ressource,
            'commentForm' => $form,
            'comments' => $comments,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ressource_edit', methods: ['GET', 'POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(\App\Security\Voter\RessourceVoter::EDIT, subject: 'ressource')]
    public function edit(Request $request, Ressource $ressource, \App\Service\RessourceManagerInterface $ressourceManager, EntityManagerInterface $entityManager): Response
    {
        // Populate DTO from existing entity
        $dto = new \App\DTO\RessourceDTO();
        $dto->title = $ressource->getTitle();
        $dto->content = $ressource->getContent();
        $dto->category = $ressource->getCategory();
        $dto->relationTypes = $ressource->getRelationTypes()->toArray();
        // Multimedia cannot be cleanly prepopulated into a file input, so it stays null in DTO.

        $form = $this->createForm(RessourceType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ressourceManager->updateFromDTO($dto, $ressource);
            $entityManager->flush();

            return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ressource/edit.html.twig', [
            'ressource' => $ressource,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ressource_delete', methods: ['POST'])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(\App\Security\Voter\RessourceVoter::DELETE, subject: 'ressource')]
    public function delete(Request $request, Ressource $ressource, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ressource->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($ressource);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
    }
}
