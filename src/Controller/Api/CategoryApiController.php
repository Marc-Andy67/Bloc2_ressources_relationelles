<?php

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

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
}
