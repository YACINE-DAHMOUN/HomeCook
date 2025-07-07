<?php
namespace App\Service;

use App\Entity\Recipes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Uid\Uuid;

class SpoonacularService
{
    private HttpClientInterface $client;
    private string $apiKey;
    private EntityManagerInterface $em;

    public function __construct(HttpClientInterface $client, string $apiKey, EntityManagerInterface $em)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->em = $em;
    }

    /**
     * Récupère les détails complets d'une recette depuis l'API Spoonacular
     */
    private function fetchRecipeDetails(int $recipeId): ?array
    {
        try {
            $response = $this->client->request('GET', "https://api.spoonacular.com/recipes/{$recipeId}/information", [
                'query' => [
                    'apiKey' => $this->apiKey,
                    'includeNutrition' => false,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
        } catch (\Exception $e) {
            echo "Erreur lors de la récupération des détails pour la recette $recipeId: " . $e->getMessage() . "\n";
        }

        return null;
    }

    /**
     * Traite et nettoie les ingrédients
     */
    private function processIngredients(array $extendedIngredients): array
    {
        $ingredients = [];
        
        foreach ($extendedIngredients as $ingredient) {
            $ingredients[] = [
                'id' => $ingredient['id'] ?? null,
                'name' => $ingredient['name'] ?? 'Ingrédient inconnu',
                'original' => $ingredient['original'] ?? '',
                'amount' => $ingredient['amount'] ?? 0,
                'unit' => $ingredient['unit'] ?? '',
                'image' => $ingredient['image'] ?? null,
            ];
        }

        return $ingredients;
    }

    /**
     * Traite et nettoie les instructions
     */
    private function processInstructions(array $analyzedInstructions): string
    {
        $allInstructions = [];
        
        foreach ($analyzedInstructions as $instruction) {
            if (isset($instruction['steps']) && is_array($instruction['steps'])) {
                foreach ($instruction['steps'] as $step) {
                    if (isset($step['step'])) {
                        $allInstructions[] = $step['number'] . '. ' . $step['step'];
                    }
                }
            }
        }

        return implode("\n\n", $allInstructions);
    }

    /**
     * Récupère et stocke en base jusqu'à $max recettes correspondant à la recherche $query
     */
    public function fetchAndStoreRecipes(string $query, Uuid $userUuid, int $max = 100): array
    {
        $recipes = [];
        $batchSize = 10; // Réduit pour éviter trop de requêtes API simultanées
        $numberPerPage = 20; // Réduit pour permettre plus de requêtes de détails
        $offset = 0;
        $duplicateCount = 0;

        echo "Début de l'importation pour: $query\n";

        while (count($recipes) < $max && $offset < 1000) {
            try {
                echo "Requête API - Offset: $offset\n";
                
                $response = $this->client->request('GET', 'https://api.spoonacular.com/recipes/complexSearch', [
                    'query' => [
                        'apiKey' => $this->apiKey,
                        'query' => $query,
                        'number' => min($numberPerPage, $max - count($recipes)),
                        'offset' => $offset,
                        'addRecipeInformation' => true,
                        'fillIngredients' => true, // Important: récupère les ingrédients basiques
                        'sort' => 'popularity',
                        'sortDirection' => 'desc'
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    echo "Erreur API: Code $statusCode\n";
                    break;
                }

                $data = $response->toArray();
                $results = $data['results'] ?? [];
                $totalResults = $data['totalResults'] ?? 0;

                echo "Résultats reçus: " . count($results) . " / Total disponible: $totalResults\n";

                if (empty($results)) {
                    echo "Aucun résultat, arrêt de la pagination\n";
                    break;
                }

                foreach ($results as $result) {
                    if (count($recipes) >= $max) {
                        break;
                    }

                    $spoonacularId = $result['id'] ?? null;
                    $title = $result['title'] ?? 'Recette inconnue';

                    // Vérifier si la recette existe déjà
                    if ($spoonacularId) {
                        $existingRecipe = $this->em->getRepository(Recipes::class)->findOneBy([
                            'spoonacularId' => $spoonacularId
                        ]);

                        if ($existingRecipe) {
                            $duplicateCount++;
                            echo "Doublon détecté: $title (ID: $spoonacularId)\n";
                            continue;
                        }
                    }

                    try {
                        echo "Traitement de: $title\n";
                        
                        $recipe = new Recipes();
                        $recipe->setName($title);
                        
                        // Description basique depuis le summary
                        $summary = $result['summary'] ?? 'Pas de description disponible';
                        $description = strip_tags($summary);
                        $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $description = substr($description, 0, 2000);
                        $recipe->setDescription($description);
                        
                        // Récupérer les détails complets si possible
                        if ($spoonacularId) {
                            echo "Récupération des détails pour: $title\n";
                            $details = $this->fetchRecipeDetails($spoonacularId);
                            
                            if ($details) {
                                // Instructions détaillées
                                if (isset($details['analyzedInstructions']) && is_array($details['analyzedInstructions'])) {
                                    $instructions = $this->processInstructions($details['analyzedInstructions']);
                                    if (!empty($instructions)) {
                                        $recipe->setInstructions($instructions);
                                    }
                                }
                                
                                // Ingrédients détaillés
                                if (isset($details['extendedIngredients']) && is_array($details['extendedIngredients'])) {
                                    $ingredients = $this->processIngredients($details['extendedIngredients']);
                                    $recipe->setIngredients($ingredients);
                                    echo "  → " . count($ingredients) . " ingrédients ajoutés\n";
                                } else {
                                    echo "  → Aucun ingrédient détaillé trouvé\n";
                                }

                                // Utiliser les données détaillées si disponibles
                                $recipe->setImageUrl($details['image'] ?? $result['image'] ?? '');
                                $recipe->setServings((int)($details['servings'] ?? $result['servings'] ?? 1));
                                $recipe->setCookingTime((int)($details['readyInMinutes'] ?? $result['readyInMinutes'] ?? 0));
                            } else {
                                echo "  → Impossible de récupérer les détails\n";
                                // Utiliser les données basiques
                                $recipe->setImageUrl($result['image'] ?? '');
                                $recipe->setServings((int)($result['servings'] ?? 1));
                                $recipe->setCookingTime((int)($result['readyInMinutes'] ?? 0));
                            }
                            
                            $recipe->setSpoonacularId($spoonacularId);
                            
                            // Délai plus long pour éviter les limites API
                            usleep(300000); // 300ms entre chaque requête de détails
                        } else {
                            // Pas d'ID Spoonacular, utiliser les données basiques
                            $recipe->setImageUrl($result['image'] ?? '');
                            $recipe->setServings((int)($result['servings'] ?? 1));
                            $recipe->setCookingTime((int)($result['readyInMinutes'] ?? 0));
                        }
                        
                        $recipe->setUser($userUuid->toString());
                        $this->em->persist($recipe);
                        $recipes[] = $recipe;

                        echo "Recette ajoutée: $title\n";

                        // Flush par batch
                        if (count($recipes) % $batchSize === 0) {
                            $this->em->flush();
                            $this->em->clear();
                            echo "Batch de $batchSize recettes sauvegardé\n";
                        }

                    } catch (\Exception $e) {
                        echo "Erreur lors de la création de la recette '$title': " . $e->getMessage() . "\n";
                        continue;
                    }
                }

                $offset += $numberPerPage;

                // Vérifier s'il y a encore des résultats
                if ($offset >= $totalResults || $offset >= 1000) {
                    echo "Fin des résultats atteinte\n";
                    break;
                }

                // Délai entre les requêtes de recherche
                usleep(500000); // 500ms

            } catch (\Exception $e) {
                echo "Erreur lors de la requête API à l'offset $offset: " . $e->getMessage() . "\n";
                break;
            }
        }

        // Flush final
        if (!empty($recipes)) {
            try {
                $this->em->flush();
                echo "Flush final effectué\n";
            } catch (\Exception $e) {
                echo "Erreur lors du flush final: " . $e->getMessage() . "\n";
            }
        }

        echo "Importation terminée: " . count($recipes) . " recettes ajoutées, $duplicateCount doublons ignorés\n";
        return $recipes;
    }

    // Le reste des méthodes reste identique...
    public function fetchMaximumRecipes(Uuid $userUuid, int $maxTotal = 10000): array
    {
        // Code existant inchangé
        $allRecipes = [];
        
        $strategies = [
            ['query' => '', 'sort' => 'popularity', 'sortDirection' => 'desc'],
            ['query' => '', 'sort' => 'time', 'sortDirection' => 'asc'],
            ['query' => '', 'sort' => 'random'],
            ['query' => 'italian', 'sort' => 'popularity'],
            ['query' => 'chinese', 'sort' => 'popularity'],
            ['query' => 'mexican', 'sort' => 'popularity'],
            ['query' => 'indian', 'sort' => 'popularity'],
            ['query' => 'french', 'sort' => 'popularity'],
            ['query' => 'american', 'sort' => 'popularity'],
            ['query' => 'japanese', 'sort' => 'popularity'],
            ['query' => 'thai', 'sort' => 'popularity'],
            ['query' => 'chicken', 'sort' => 'popularity'],
            ['query' => 'beef', 'sort' => 'popularity'],
            ['query' => 'pasta', 'sort' => 'popularity'],
            ['query' => 'rice', 'sort' => 'popularity'],
            ['query' => 'vegetarian', 'sort' => 'popularity'],
            ['query' => 'dessert', 'sort' => 'popularity'],
            ['query' => 'soup', 'sort' => 'popularity'],
            ['query' => 'salad', 'sort' => 'popularity'],
        ];

        foreach ($strategies as $strategy) {
            if (count($allRecipes) >= $maxTotal) {
                break;
            }

            $remainingQuota = $maxTotal - count($allRecipes);
            $recipes = $this->fetchRecipesByStrategy($userUuid, $strategy, min(500, $remainingQuota));
            $allRecipes = array_merge($allRecipes, $recipes);
            
            echo "Stratégie '" . $strategy['query'] . "' terminée. Total: " . count($allRecipes) . " recettes\n";
        }

        return $allRecipes;
    }

    private function fetchRecipesByStrategy(Uuid $userUuid, array $strategy, int $max): array
    {
        $recipes = [];
        $numberPerPage = 20; // Réduit pour permettre les requêtes de détails
        $offset = 0;

        while (count($recipes) < $max && $offset < 1000) {
            try {
                $queryParams = [
                    'apiKey' => $this->apiKey,
                    'number' => min($numberPerPage, $max - count($recipes)),
                    'offset' => $offset,
                    'addRecipeInformation' => true,
                    'fillIngredients' => true,
                ];

                if (!empty($strategy['query'])) {
                    $queryParams['query'] = $strategy['query'];
                }
                if (isset($strategy['sort'])) {
                    $queryParams['sort'] = $strategy['sort'];
                }
                if (isset($strategy['sortDirection'])) {
                    $queryParams['sortDirection'] = $strategy['sortDirection'];
                }

                $response = $this->client->request('GET', 'https://api.spoonacular.com/recipes/complexSearch', [
                    'query' => $queryParams,
                ]);

                $data = $response->toArray();
                $results = $data['results'] ?? [];

                if (empty($results)) {
                    break;
                }

                foreach ($results as $result) {
                    if (count($recipes) >= $max) {
                        break;
                    }

                    $spoonacularId = $result['id'] ?? null;
                    
                    if ($spoonacularId) {
                        $existingRecipe = $this->em->getRepository(Recipes::class)->findOneBy([
                            'spoonacularId' => $spoonacularId
                        ]);

                        if ($existingRecipe) {
                            continue;
                        }
                    }

                    $recipe = new Recipes();
                    $recipe->setName($result['title'] ?? 'Recette inconnue');
                    
                    $summary = $result['summary'] ?? 'Pas de description';
                    $description = substr(strip_tags(html_entity_decode($summary, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 2000);
                    $recipe->setDescription($description);
                    
                    // Récupérer les détails si possible
                    if ($spoonacularId) {
                        $details = $this->fetchRecipeDetails($spoonacularId);
                        if ($details) {
                            if (isset($details['analyzedInstructions']) && is_array($details['analyzedInstructions'])) {
                                $instructions = $this->processInstructions($details['analyzedInstructions']);
                                if (!empty($instructions)) {
                                    $recipe->setInstructions($instructions);
                                }
                            }
                            
                            if (isset($details['extendedIngredients']) && is_array($details['extendedIngredients'])) {
                                $ingredients = $this->processIngredients($details['extendedIngredients']);
                                $recipe->setIngredients($ingredients);
                            }
                        }
                        usleep(300000); // Délai pour éviter les limites API
                    }
                    
                    $recipe->setImageUrl($result['image'] ?? '');
                    $recipe->setServings((int)($result['servings'] ?? 1));
                    $recipe->setCookingTime((int)($result['readyInMinutes'] ?? 0));
                    $recipe->setUser($userUuid->toString());
                    
                    if ($spoonacularId) {
                        $recipe->setSpoonacularId($spoonacularId);
                    }

                    $this->em->persist($recipe);
                    $recipes[] = $recipe;

                    if (count($recipes) % 10 === 0) {
                        $this->em->flush();
                        $this->em->clear();
                    }
                }

                $offset += $numberPerPage;
                usleep(500000); // Délai entre les requêtes

            } catch (\Exception $e) {
                echo "Erreur dans fetchRecipesByStrategy: " . $e->getMessage() . "\n";
                break;
            }
        }

        if (!empty($recipes)) {
            $this->em->flush();
        }

        return $recipes;
    }

    public function searchRecipes(string $query): array
    {
        try {
            $response = $this->client->request('GET', 'https://api.spoonacular.com/recipes/complexSearch', [
                'query' => [
                    'apiKey' => $this->apiKey,
                    'query' => $query,
                    'number' => 5,
                    'addRecipeInformation' => true,
                    'fillIngredients' => true,
                ],
            ]);

            return $response->toArray()['results'] ?? [];
        } catch (\Exception $e) {
            echo "Erreur dans searchRecipes: " . $e->getMessage() . "\n";
            return [];
        }
    }
}