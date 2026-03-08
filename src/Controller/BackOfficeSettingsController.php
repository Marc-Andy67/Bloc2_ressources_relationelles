<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\RelationType;
use App\Form\CategoryType;
use App\Form\RelationTypeType;
use App\Repository\CategoryRepository;
use App\Repository\RelationTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/settings')]
#[IsGranted('ROLE_ADMIN')]
class BackOfficeSettingsController extends AbstractController
{
    // ==========================================
    // CATEGORIES
    // ==========================================

    #[Route('/categories', name: 'app_admin_categories')]
    public function categories(CategoryRepository $categoryRepository): Response
    {
        return $this->render('back_office/settings/categories.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/category/new', name: 'app_admin_category_new')]
    #[Route('/category/{id}/edit', name: 'app_admin_category_edit')]
    public function categoryForm(Request $request, EntityManagerInterface $em, ?Category $category = null): Response
    {
        if (!$category) {
            $category = new Category();
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'La catégorie a été enregistrée avec succès.');
            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('back_office/settings/_form.html.twig', [
            'form' => $form->createView(),
            'title' => $category->getId() ? 'Modifier la catégorie' : 'Nouvelle catégorie',
            'back_route' => 'app_admin_categories'
        ]);
    }

    #[Route('/category/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
    public function categoryDelete(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            if (count($category->getRessources()) > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle est utilisée par des ressources.');
            } else {
                try {
                    $em->remove($category);
                    $em->flush();
                    $this->addFlash('success', 'Catégorie supprimée avec succès.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle est actuellement utilisée.');
                }
            }
        }

        return $this->redirectToRoute('app_admin_categories');
    }

    // ==========================================
    // RELATION TYPES
    // ==========================================

    #[Route('/relation-types', name: 'app_admin_relation_types')]
    public function relationTypes(RelationTypeRepository $relationTypeRepository): Response
    {
        return $this->render('back_office/settings/relation_types.html.twig', [
            'relation_types' => $relationTypeRepository->findAll(),
        ]);
    }

    #[Route('/relation-type/new', name: 'app_admin_relation_type_new')]
    #[Route('/relation-type/{id}/edit', name: 'app_admin_relation_type_edit')]
    public function relationTypeForm(Request $request, EntityManagerInterface $em, ?RelationType $relationType = null): Response
    {
        if (!$relationType) {
            $relationType = new RelationType();
        }

        $form = $this->createForm(RelationTypeType::class, $relationType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($relationType);
            $em->flush();

            $this->addFlash('success', 'Le type de relation a été enregistré avec succès.');
            return $this->redirectToRoute('app_admin_relation_types');
        }

        return $this->render('back_office/settings/_form.html.twig', [
            'form' => $form->createView(),
            'title' => $relationType->getId() ? 'Modifier le type de relation' : 'Nouveau type de relation',
            'back_route' => 'app_admin_relation_types'
        ]);
    }

    #[Route('/relation-type/{id}/delete', name: 'app_admin_relation_type_delete', methods: ['POST'])]
    public function relationTypeDelete(Request $request, RelationType $relationType, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $relationType->getId(), $request->request->get('_token'))) {
            $em->remove($relationType);
            $em->flush();
            $this->addFlash('success', 'Type de relation supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_relation_types');
    }
}
