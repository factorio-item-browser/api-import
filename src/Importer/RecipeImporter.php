<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Recipe as DatabaseRecipe;
use FactorioItemBrowser\Api\Database\Entity\RecipeIngredient as DatabaseIngredient;
use FactorioItemBrowser\Api\Database\Entity\RecipeProduct as DatabaseProduct;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\Entity\Recipe\Ingredient as ExportIngredient;
use FactorioItemBrowser\ExportData\Entity\Recipe\Product as ExportProduct;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The importer of recipes.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class RecipeImporter implements ImporterInterface
{
    /**
     * The crafting category importer.
     * @var CraftingCategoryImporter
     */
    protected $craftingCategoryImporter;

    /**
     * The id calculator.
     * @var IdCalculator
     */
    protected $idCalculator;

    /**
     * The item importer.
     * @var ItemImporter
     */
    protected $itemImporter;

    /**
     * The recipe repository.
     * @var RecipeRepository
     */
    protected $recipeRepository;

    /**
     * The recipes.
     * @var array|DatabaseRecipe[]
     */
    protected $recipes = [];

    /**
     * Initializes the importer.
     * @param CraftingCategoryImporter $craftingCategoryImporter
     * @param IdCalculator $idCalculator
     * @param ItemImporter $itemImporter
     * @param RecipeRepository $recipeRepository
     */
    public function __construct(
        CraftingCategoryImporter $craftingCategoryImporter,
        IdCalculator $idCalculator,
        ItemImporter $itemImporter,
        RecipeRepository $recipeRepository
    ) {
        $this->craftingCategoryImporter = $craftingCategoryImporter;
        $this->idCalculator = $idCalculator;
        $this->itemImporter = $itemImporter;
        $this->recipeRepository = $recipeRepository;
    }

    /**
     * Prepares the data provided for the other importers.
     * @param ExportData $exportData
     */
    public function prepare(ExportData $exportData): void
    {
        $this->recipes = [];
    }

    /**
     * Actually parses the data, having access to data provided by other importers.
     * @param ExportData $exportData
     * @throws ImportException
     */
    public function parse(ExportData $exportData): void
    {
        $ids = [];
        foreach ($exportData->getCombination()->getRecipes() as $exportRecipe) {
            $databaseRecipe = $this->mapRecipe($exportRecipe);
            $ids[] = $databaseRecipe->getId();

            $this->add($databaseRecipe);
        }

        foreach ($this->recipeRepository->findByIds($ids) as $recipe) {
            $this->add($recipe);
        }
    }

    /**
     * Maps the export recipe to a database one.
     * @param ExportRecipe $exportRecipe
     * @return DatabaseRecipe
     * @throws ImportException
     */
    protected function mapRecipe(ExportRecipe $exportRecipe): DatabaseRecipe
    {
        $databaseRecipe = new DatabaseRecipe();
        $databaseRecipe->setName($exportRecipe->getName())
                       ->setMode($exportRecipe->getMode())
                       ->setCraftingCategory(
                           $this->craftingCategoryImporter->getByName($exportRecipe->getCraftingCategory())
                       )
                       ->setCraftingTime($exportRecipe->getCraftingTime());

        $this->mapIngredients($exportRecipe, $databaseRecipe);
        $this->mapProducts($exportRecipe, $databaseRecipe);

        $databaseRecipe->setId($this->idCalculator->calculateIdOfRecipe($databaseRecipe));
        return $databaseRecipe;
    }

    /**
     * Maps the ingredients from the export recipe to the database one.
     * @param ExportRecipe $exportRecipe
     * @param DatabaseRecipe $databaseRecipe
     * @throws ImportException
     */
    protected function mapIngredients(ExportRecipe $exportRecipe, DatabaseRecipe $databaseRecipe): void
    {
        foreach ($exportRecipe->getIngredients() as $index => $exportIngredient) {
            $databaseIngredient = $this->mapIngredient($exportIngredient);
            $databaseIngredient->setRecipe($databaseRecipe)
                               ->setOrder($index);
            $databaseRecipe->getIngredients()->add($databaseIngredient);
        }
    }

    /**
     * Maps the export ingredient to a database one.
     * @param ExportIngredient $exportIngredient
     * @return DatabaseIngredient
     * @throws ImportException
     */
    protected function mapIngredient(ExportIngredient $exportIngredient): DatabaseIngredient
    {
        $item = $this->itemImporter->getByTypeAndName(
            $exportIngredient->getType(),
            $exportIngredient->getName()
        );

        $databaseIngredient = new DatabaseIngredient();
        $databaseIngredient->setItem($item)
                           ->setAmount($exportIngredient->getAmount());
        return $databaseIngredient;
    }

    /**
     * Maps the products from the export recipe to the database one.
     * @param ExportRecipe $exportRecipe
     * @param DatabaseRecipe $databaseRecipe
     * @throws ImportException
     */
    protected function mapProducts(ExportRecipe $exportRecipe, DatabaseRecipe $databaseRecipe): void
    {
        foreach ($exportRecipe->getProducts() as $index => $exportProduct) {
            $databaseProduct = $this->mapProduct($exportProduct);
            $databaseProduct->setRecipe($databaseRecipe)
                            ->setOrder($index);
            $databaseRecipe->getProducts()->add($databaseProduct);
        }
    }
    
    /**
     * Maps the export product to a database one.
     * @param ExportProduct $exportProduct
     * @return DatabaseProduct
     * @throws ImportException
     */
    protected function mapProduct(ExportProduct $exportProduct): DatabaseProduct
    {
        $item = $this->itemImporter->getByTypeAndName(
            $exportProduct->getType(),
            $exportProduct->getName()
        );

        $databaseProduct = new DatabaseProduct();
        $databaseProduct->setItem($item)
                        ->setAmountMin($exportProduct->getAmountMin())
                        ->setAmountMax($exportProduct->getAmountMax())
                        ->setProbability($exportProduct->getProbability());
        return $databaseProduct;
    }

    /**
     * Adds the recipe to the local properties.
     * @param DatabaseRecipe $recipe
     */
    protected function add(DatabaseRecipe $recipe): void
    {
        $this->recipes[$recipe->getId()->toString()] = $recipe;
    }

    /**
     * Persists the parsed data to the combination.
     * @param EntityManagerInterface $entityManager
     * @param Combination $combination
     */
    public function persist(EntityManagerInterface $entityManager, Combination $combination): void
    {
        $combination->getRecipes()->clear();
        foreach ($this->recipes as $recipe) {
            $entityManager->persist($recipe);
            $combination->getRecipes()->add($recipe);
        }
    }

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void
    {
        $this->recipeRepository->removeOrphans();

        // We may have created new orphans, so better be safe and cleanup again.
        $this->itemImporter->cleanup();
        $this->craftingCategoryImporter->cleanup();
    }
}
