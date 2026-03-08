<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ProgressionRepository;
use App\Repository\RelationTypeRepository;
use App\Repository\RessourceRepository;
use App\Repository\UserRepository;
use App\Service\ProgressionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
class BackOfficeController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_MODERATOR')]
    public function dashboard(
        Request $request,
        RessourceRepository $ressourceRepository,
        \App\Repository\CategoryRepository $categoryRepository,
        \App\Repository\RelationTypeRepository $relationTypeRepository
    ): Response {
        $filters = $request->query->all();
        $filters['status'] = 'pending'; // Force looking only at pending resources

        $pendingResources = $ressourceRepository->findByFilters($filters);

        return $this->render('back_office/dashboard.html.twig', [
            'pending_resources' => $pendingResources,
            'categories' => $categoryRepository->findAll(),
            'relationTypes' => $relationTypeRepository->findAll(),
            'currentFilters' => $filters,
        ]);
    }

    #[Route('/ressource/{id}/approve', name: 'app_admin_ressource_approve', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function approve(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('approve' . $ressource->getId(), (string) $request->request->get('_token'))) {
            $ressource->setStatus('validated');
            $em->flush();
            $this->addFlash('success', 'Ressource approuvée avec succès.');
        }
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/ressource/{id}/reject', name: 'app_admin_ressource_reject', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function reject(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('reject' . $ressource->getId(), (string) $request->request->get('_token'))) {
            $ressource->setStatus('rejected');
            $em->flush();
            $this->addFlash('success', 'Ressource refusée.');
        }
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/resources', name: 'app_admin_resources')]
    #[IsGranted('ROLE_ADMIN')]
    public function allResources(Request $request, RessourceRepository $ressourceRepository): Response
    {
        $statusFilter = $request->query->get('status');
        
        $criteria = [];
        if ($statusFilter) {
            $criteria['status'] = $statusFilter;
        }

        $resources = $ressourceRepository->findBy($criteria, ['creationDate' => 'DESC']);

        return $this->render('back_office/resources_all.html.twig', [
            'resources' => $resources,
            'current_status' => $statusFilter,
        ]);
    }

    #[Route('/ressource/{id}/suspend', name: 'app_admin_ressource_suspend', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function suspend(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('suspend' . $ressource->getId(), (string) $request->request->get('_token'))) {
            $ressource->setStatus('suspended');
            $em->flush();
            $this->addFlash('success', 'La ressource a été suspendue. Elle n\'est plus visible publiquement.');
        }
        return $this->redirectToRoute('app_admin_resources');
    }

    #[Route('/ressource/{id}/delete', name: 'app_admin_ressource_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteResource(Request $request, Ressource $ressource, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ressource->getId(), (string) $request->request->get('_token'))) {
            $em->remove($ressource);
            $em->flush();
            $this->addFlash('success', 'La ressource a été définitivement supprimée.');
        }
        return $this->redirectToRoute('app_admin_resources');
    }

    #[Route('/analytics', name: 'app_admin_analytics', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function analytics(
        Request $request,
        RessourceRepository $ressourceRepository,
        UserRepository $userRepository,
        ProgressionRepository $progressionRepository,
        CategoryRepository $categoryRepository,
        RelationTypeRepository $relationTypeRepository
    ): Response {
        $filters = $request->query->all();

        // 1. Statistiques Globales (Créations)
        $totalResources = $ressourceRepository->countFilteredResources($filters, 'validated');
        $pendingResources = $ressourceRepository->countFilteredResources($filters, 'pending');
        $totalUsers = $userRepository->count([]);

        // 2. Statistiques d'Exploitation (via Progression)
        $viewsCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_VIEW]));
        $favoritesCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_FAVORITE]));
        $savedCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_SAVE]));
        $likesCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_LIKE]));
        $commentsCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_COMMENT]));

        // Filtres pour le formulaire
        $categories = $categoryRepository->findAll();
        $relationTypes = $relationTypeRepository->findAll();
        
        $typesResult = $ressourceRepository->createQueryBuilder('r')
            ->select('DISTINCT r.type')
            ->where('r.type IS NOT NULL')
            ->getQuery()
            ->getScalarResult();
        $typesList = array_map(fn($t) => $t['type'], $typesResult);

        return $this->render('back_office/analytics.html.twig', [
            'total_resources' => $totalResources,
            'pending_resources' => $pendingResources,
            'total_users' => $totalUsers,
            
            'views_count' => $viewsCount,
            'favorites_count' => $favoritesCount,
            'saved_count' => $savedCount,
            'likes_count' => $likesCount,
            'comments_count' => $commentsCount,

            'categories' => $categories,
            'relationTypes' => $relationTypes,
            'types' => $typesList,
            'currentFilters' => $filters,
        ]);
    }

    #[Route('/analytics/export', name: 'app_admin_analytics_export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function exportAnalytics(
        Request $request,
        RessourceRepository $ressourceRepository,
        UserRepository $userRepository,
        ProgressionRepository $progressionRepository
    ): Response {
        $filters = $request->query->all();

        // Recalculer les stats avec les mêmes filtres
        $totalResources = $ressourceRepository->countFilteredResources($filters, 'validated');
        $pendingResources = $ressourceRepository->countFilteredResources($filters, 'pending');
        $totalUsers = $userRepository->count([]);

        $viewsCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_VIEW]));
        $favoritesCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_FAVORITE]));
        $savedCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_SAVE]));
        $likesCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_LIKE]));
        $commentsCount = $progressionRepository->countFilteredProgressions(array_merge($filters, ['action' => ProgressionService::ACTION_COMMENT]));

        // Construction du CSV
        $rows = [
            ['Statistique', 'Valeur'],
            ['Total Ressources (Publiées)', $totalResources],
            ['Ressources en attente', $pendingResources],
            ['Total Utilisateurs', $totalUsers],
            ['Total Consultations (Vues)', $viewsCount],
            ['Total Mises en Favoris', $favoritesCount],
            ['Total Mises de Coté', $savedCount],
            ['Total Likes', $likesCount],
            ['Total Commentaires', $commentsCount],
        ];

        // Format de filtre appliqué
        $rows[] = ['', ''];
        $rows[] = ['Filtres Appliqués', ''];
        $rows[] = ['Date début', $filters['date_debut'] ?? 'Toutes'];
        $rows[] = ['Date fin', $filters['date_fin'] ?? 'Toutes'];
        $rows[] = ['Catégorie', $filters['categorie'] ?? 'Toutes'];
        $rows[] = ['Type de relation', $filters['type_relation'] ?? 'Toutes'];
        $rows[] = ['Type de ressource', $filters['type_ressource'] ?? 'Tous'];

        // Construction du CSV avec fputcsv
        $fp = fopen('php://temp', 'r+');
        // Ajout du BOM UTF-8 pour forcer Excel à lire les accents correctement
        fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        foreach ($rows as $row) {
            fputcsv($fp, $row, ';');
        }
        
        rewind($fp);
        $csvContent = stream_get_contents($fp);
        fclose($fp);

        $response = new Response($csvContent);
        
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="statistiques_ressources.csv"');

        return $response;
    }

    #[Route('/users', name: 'app_admin_users')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('back_office/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/{id}/toggle-role', name: 'app_admin_users_toggle_role', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function toggleRole(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle-role' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $role = $request->getPayload()->getString('role');

            // Validate the role
            $allowedRoles = ['ROLE_USER', 'ROLE_MODERATOR', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];
            if (in_array($role, $allowedRoles)) {
                $roles = $user->getRoles();

                // Toggle the role (except ROLE_USER which is mandatory)
                if ($role !== 'ROLE_USER') {
                    if (in_array($role, $roles)) {
                        // Remove role
                        $roles = array_diff($roles, [$role]);
                    } else {
                        // Add role
                        $roles[] = $role;

                        // Enforce hierarchy visually/structurally
                        if ($role === 'ROLE_SUPER_ADMIN') {
                            $roles[] = 'ROLE_ADMIN';
                            $roles[] = 'ROLE_MODERATOR';
                        } elseif ($role === 'ROLE_ADMIN') {
                            $roles[] = 'ROLE_MODERATOR';
                        }
                    }

                    $user->setRoles(array_unique($roles));
                    $em->flush();
                    $this->addFlash('success', 'Rôle mis à jour avec succès.');
                }
            } else {
                $this->addFlash('error', 'Rôle invalide.');
            }
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/toggle-active', name: 'app_admin_users_toggle_active', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function toggleActive(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle-active' . $user->getId(), $request->getPayload()->getString('_token'))) {
            // Cannot suspend yourself
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas suspendre votre propre compte.');
                return $this->redirectToRoute('app_admin_users');
            }

            // Cannot suspend another super admin to prevent lockouts
            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->addFlash('error', 'Impossible de suspendre un Super Administrateur.');
                return $this->redirectToRoute('app_admin_users');
            }

            $user->setIsActive(!$user->isActive());
            $em->flush();

            if ($user->isActive()) {
                $this->addFlash('success', 'Le compte de ' . $user->getUserIdentifier() . ' a été réactivé.');
            } else {
                $this->addFlash('warning', 'Le compte de ' . $user->getUserIdentifier() . ' a été suspendu.');
            }
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
