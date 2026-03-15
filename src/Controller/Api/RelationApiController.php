<?php

namespace App\Controller\Api;

use App\Repository\RelationTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/relation-types', name: 'api_relation_types_')]
class RelationApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(RelationTypeRepository $relationTypeRepository): JsonResponse
    {
        $relations = $relationTypeRepository->findAll();
        
        $data = array_map(function ($relation) {
            return [
                'id' => (string) $relation->getId(),
                'name' => $relation->getName(),
            ];
        }, $relations);

        return $this->json($data);
    }
}
