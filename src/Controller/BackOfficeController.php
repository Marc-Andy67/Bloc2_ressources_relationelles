<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Entity\User;
use App\Repository\RessourceRepository;
use App\Repository\UserRepository;
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
        if ($this->isCsrfTokenValid('approve' . $ressource->getId(), $request->getPayload()->getString('_token'))) {
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
        if ($this->isCsrfTokenValid('reject' . $ressource->getId(), $request->getPayload()->getString('_token'))) {
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
        if ($this->isCsrfTokenValid('suspend' . $ressource->getId(), $request->getPayload()->getString('_token'))) {
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
        if ($this->isCsrfTokenValid('delete' . $ressource->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($ressource);
            $em->flush();
            $this->addFlash('success', 'La ressource a été définitivement supprimée.');
        }
        return $this->redirectToRoute('app_admin_resources');
    }

    #[Route('/analytics', name: 'app_admin_analytics')]
    #[IsGranted('ROLE_ADMIN')]
    public function analytics(RessourceRepository $ressourceRepository, UserRepository $userRepository): Response
    {
        // Simple analytics for the Admin
        $totalResources = $ressourceRepository->count([]);
        $validatedResources = $ressourceRepository->count(['status' => 'validated']);
        $pendingResources = $ressourceRepository->count(['status' => 'pending']);
        $rejectedResources = $ressourceRepository->count(['status' => 'rejected']);
        $totalUsers = $userRepository->count([]);

        return $this->render('back_office/analytics.html.twig', [
            'total_resources' => $totalResources,
            'validated_resources' => $validatedResources,
            'pending_resources' => $pendingResources,
            'rejected_resources' => $rejectedResources,
            'total_users' => $totalUsers,
        ]);
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
}
