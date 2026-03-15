<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/categories', name: 'api_categories_')]
class CategoryApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();
        
        $data = array_map(function ($category) {
            return [
                'id' => (string) $category->getId(),
                'name' => $category->getName(),
            ];
        }, $categories);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom est requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $category = new Category();
        $category->setName($data['name']);
        $entityManager->persist($category);
        $entityManager->flush();
        return $this->json([
            'id' => (string) $category->getId(),
            'name' => $category->getName(),
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Category $category,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['name'])) {
            $category->setName($data['name']);
        }
        $entityManager->flush();
        return $this->json([
            'id' => (string) $category->getId(),
            'name' => $category->getName(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Category $category,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $entityManager->remove($category);
        $entityManager->flush();
        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
