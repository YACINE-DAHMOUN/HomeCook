<?php
namespace App\Controller;

use App\Service\SpoonacularService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class RecipeImportController extends AbstractController
{
    #[Route('/api/recipes/import/{query}', name: 'import_recipes', methods: ['GET'])]
    public function import(string $query, SpoonacularService $spoonacular): JsonResponse
    {
        // ⚠️ Ici tu dois récupérer l'UUID du user connecté
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getUserIdentifier')) {
            return $this->json(['error' => 'User not authenticated or missing getUserIdentifier()'], 401);
        }
        $userUuid = Uuid::fromString($user->getUserIdentifier());

        $recipes = $spoonacular->fetchAndStoreRecipes($query, $userUuid);

        return $this->json(['status' => 'ok', 'imported' => count($recipes)]);
    }
}
