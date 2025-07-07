<?php

namespace App\Controller;

use App\Service\SpoonacularService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class RecipeController extends AbstractController
{
    #[Route('/api/recipes/search/{query}', name: 'recipe_search')]
    public function search(string $query, SpoonacularService $spoonacular): JsonResponse
    {
        $data = $spoonacular->searchRecipes($query);
        return $this->json($data);
    }
}
