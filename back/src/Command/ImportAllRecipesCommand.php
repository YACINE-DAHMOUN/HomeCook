<?php
namespace App\Command;

use App\Service\SpoonacularService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:import-all-recipes',
    description: 'Importe le maximum de recettes depuis Spoonacular',
)]
class ImportAllRecipesCommand extends Command
{
    private SpoonacularService $spoonacularService;
   // Ajoutez ces catégories supplémentaires dans votre ImportAllRecipesCommand.php
private array $categories = [
    '', // Requête vide pour tout récupérer
    
    // Types de cuisine (plus complets)
    'italian', 'chinese', 'mexican', 'indian', 'french', 'american', 'japanese', 'thai', 'greek', 'spanish',
    'korean', 'vietnamese', 'german', 'british', 'mediterranean', 'middle eastern', 'cajun', 'southern',
    'moroccan', 'turkish', 'lebanese', 'brazilian', 'peruvian', 'cuban', 'ethiopian', 'russian',
    
    // Types de plats étendus
    'main course', 'side dish', 'dessert', 'appetizer', 'salad', 'bread', 'breakfast', 'soup', 'beverage',
    'sauce', 'marinade', 'snack', 'cocktail', 'lunch', 'dinner', 'brunch', 'fingerfood', 'condiment',
    
    // Ingrédients très populaires
    'chicken', 'beef', 'pork', 'fish', 'salmon', 'shrimp', 'pasta', 'rice', 'potato', 'tomato',
    'cheese', 'egg', 'mushroom', 'garlic', 'onion', 'lemon', 'chocolate', 'vanilla', 'strawberry',
    'apple', 'banana', 'vegetables', 'meat', 'seafood', 'beans', 'spinach', 'broccoli', 'carrot',
    'bell pepper', 'zucchini', 'eggplant', 'cucumber', 'avocado', 'corn', 'peas', 'asparagus',
    
    // Protéines alternatives
    'tofu', 'tempeh', 'quinoa', 'lentils', 'chickpeas', 'black beans', 'turkey', 'duck', 'lamb',
    'crab', 'lobster', 'scallops', 'tuna', 'cod', 'tilapia',
    
    // Régimes alimentaires étendus
    'vegetarian', 'vegan', 'gluten free', 'dairy free', 'keto', 'paleo', 'low carb', 'healthy',
    'low fat', 'high protein', 'low sodium', 'sugar free', 'whole30', 'mediterranean diet',
    
    // Méthodes de cuisson étendues
    'baked', 'grilled', 'fried', 'roasted', 'steamed', 'slow cooker', 'instant pot', 'air fryer', 
    'no bake', 'pressure cooker', 'sous vide', 'smoked', 'braised', 'sauteed', 'poached',
    
    // Occasions étendues
    'holiday', 'christmas', 'thanksgiving', 'easter', 'summer', 'winter', 'party', 'quick', 'easy',
    'romantic', 'kids', 'budget', 'elegant', 'comfort food', 'picnic', 'bbq', 'potluck',
    
    // Types de desserts
    'cake', 'cookies', 'pie', 'ice cream', 'pudding', 'mousse', 'tart', 'cupcake', 'brownie',
    
    // Boissons
    'smoothie', 'juice', 'coffee', 'tea', 'wine', 'beer', 'mocktail',
    
    // Temps de préparation
    '15 minute', '30 minute', 'one pot', 'sheet pan', 'make ahead', 'freezer friendly',
    
    // Repas spécifiques
    'pizza', 'burger', 'sandwich', 'wrap', 'stir fry', 'curry', 'stew', 'casserole', 'risotto',
    'tacos', 'pasta salad', 'fried rice', 'noodles', 'pancakes', 'waffles', 'omelette'
];

    public function __construct(SpoonacularService $spoonacularService)
    {
        parent::__construct();
        $this->spoonacularService = $spoonacularService;
    }

    protected function configure(): void
    {
        $this
            ->addOption('max-per-category', null, InputOption::VALUE_OPTIONAL, 'Nombre max de recettes par catégorie', 500)
            ->addOption('max-total', null, InputOption::VALUE_OPTIONAL, 'Nombre max total de recettes', 10000)
            ->addOption('delay', null, InputOption::VALUE_OPTIONAL, 'Délai entre les requêtes en secondes', 1)
            ->addOption('strategy', null, InputOption::VALUE_OPTIONAL, 'Stratégie: categories|maximum', 'categories');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $maxPerCategory = (int) $input->getOption('max-per-category');
        $maxTotal = (int) $input->getOption('max-total');
        $delay = (int) $input->getOption('delay');
        $strategy = $input->getOption('strategy');
        $userUuid = Uuid::v4();

        $output->writeln('<info>🚀 Début de l\'importation de recettes depuis Spoonacular</info>');
        $output->writeln(sprintf('📊 Stratégie: %s', $strategy));
        $output->writeln(sprintf('🎯 Max total: %d recettes', $maxTotal));

        if ($strategy === 'maximum') {
            return $this->executeMaximumStrategy($userUuid, $maxTotal, $output);
        } else {
            return $this->executeCategoriesStrategy($userUuid, $maxPerCategory, $delay, $output);
        }
    }

    private function executeMaximumStrategy(Uuid $userUuid, int $maxTotal, OutputInterface $output): int
    {
        $output->writeln('<comment>📥 Utilisation de la stratégie "maximum" pour importer le plus de recettes possible</comment>');
        
        try {
            $recipes = $this->spoonacularService->fetchMaximumRecipes($userUuid, $maxTotal);
            $totalImported = count($recipes);
            
            $output->writeln('');
            $output->writeln('<info>✅ Importation terminée avec succès!</info>');
            $output->writeln(sprintf('<info>📊 Total de recettes importées: %d</info>', $totalImported));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $output->writeln('<error>❌ Erreur lors de l\'importation: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    private function executeCategoriesStrategy(Uuid $userUuid, int $maxPerCategory, int $delay, OutputInterface $output): int
    {
        $output->writeln(sprintf('📂 Catégories à traiter: %d', count($this->categories)));
        $output->writeln(sprintf('🔢 Max par catégorie: %d', $maxPerCategory));

        $progressBar = new ProgressBar($output, count($this->categories));
        $progressBar->setFormat('debug');
        $progressBar->start();

        $totalImported = 0;
        $errors = [];
        $successfulCategories = 0;

        foreach ($this->categories as $category) {
            $categoryName = empty($category) ? 'TOUTES' : $category;
            
            try {
                $output->writeln('');
                $output->writeln(sprintf('<comment>📥 Importation pour: %s</comment>', $categoryName));
                
                $recipes = $this->spoonacularService->fetchAndStoreRecipes($category, $userUuid, $maxPerCategory);
                $imported = count($recipes);
                $totalImported += $imported;
                $successfulCategories++;
                
                $output->writeln(sprintf('<info>✅ %d recettes importées pour "%s"</info>', $imported, $categoryName));
                
                // Délai pour respecter les limites de l'API
                if ($delay > 0) {
                    $output->writeln(sprintf('<comment>⏳ Attente de %d seconde(s)...</comment>', $delay));
                    sleep($delay);
                }
                
            } catch (\Exception $e) {
                $errorMessage = sprintf('Erreur pour "%s": %s', $categoryName, $e->getMessage());
                $errors[] = $errorMessage;
                $output->writeln(sprintf('<error>❌ %s</error>', $errorMessage));
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln('');
        $output->writeln('<info>🎉 Importation terminée!</info>');
        $output->writeln(sprintf('<info>📊 Total de recettes importées: %d</info>', $totalImported));
        $output->writeln(sprintf('<info>✅ Catégories réussies: %d/%d</info>', $successfulCategories, count($this->categories)));
        
        if (!empty($errors)) {
            $output->writeln('');
            $output->writeln(sprintf('<comment>⚠️  %d erreur(s) rencontrée(s):</comment>', count($errors)));
            foreach ($errors as $error) {
                $output->writeln(sprintf('<error>  • %s</error>', $error));
            }
        }

        // Recommandations finales
        $output->writeln('');
        $output->writeln('<comment>💡 Conseils:</comment>');
        $output->writeln('<comment>  • Pour plus de recettes, utilisez: --strategy=maximum</comment>');
        $output->writeln('<comment>  • Pour éviter les limites API, augmentez: --delay=2</comment>');
        
        return $totalImported > 0 ? Command::SUCCESS : Command::FAILURE;
    }
}