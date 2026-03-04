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
    public function new(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
    {
        $ressource = new Ressource();
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $ressource->setAuthor($user);
            $ressource->setStatus(true); // Publié par défaut
            $ressource->setCreationDate(new \DateTime());

            $multimediaFile = $form->get('multimedia')->getData();

            if ($multimediaFile) {
                $ressource->setType($multimediaFile->getMimeType());
                $ressource->setSize($multimediaFile->getSize());

                $originalFilename = pathinfo($multimediaFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $multimediaFile->guessExtension();

                try {
                    $multimediaFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/multimedia',
                        $newFilename
                    );
                    $currentContent = $ressource->getContent() ?? '';
                    $ressource->setContent($currentContent . "\n\n[Fichier multimédia attaché : /uploads/multimedia/" . $newFilename . "]");
                } catch (\Exception $e) {
                    // Handle exception quietly or flash a message
                }
            } else {
                $ressource->setType('text/post');
                $content = $ressource->getContent() ?? '';
                $ressource->setSize(strlen($content));
            }

            $entityManager->persist($ressource);
            $entityManager->flush();

            return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ressource/new.html.twig', [
            'ressource' => $ressource,
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
    public function edit(Request $request, Ressource $ressource, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ressource/edit.html.twig', [
            'ressource' => $ressource,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ressource_delete', methods: ['POST'])]
    public function delete(Request $request, Ressource $ressource, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ressource->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($ressource);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
    }
}
