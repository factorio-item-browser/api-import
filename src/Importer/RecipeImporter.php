<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use FactorioItemBrowser\Api\Database\Data\RecipeData;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Recipe as DatabaseRecipe;
use FactorioItemBrowser\Api\Database\Entity\RecipeIngredient;
use FactorioItemBrowser\Api\Database\Entity\RecipeProduct;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Import\Service\CraftingCategoryService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Service\ItemService;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\Entity\Recipe\Ingredient;
use FactorioItemBrowser\ExportData\Entity\Recipe\Product;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The importer of the recipes.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class RecipeImporter extends AbstractImporter
{
    /**
     * The service of the crafting categories.
     * @var CraftingCategoryService
     */
    protected $craftingCategoryService;

    /**
     * The service of the items.
     * @var ItemService
     */
    protected $itemService;

    /**
     * The repository of the recipes.
     * @var RecipeRepository
     */
    protected $recipeRepository;

    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * RecipeImporter constructor.
     * @param CraftingCategoryService $craftingCategoryService
     * @param EntityManager $entityManager
     * @param ItemService $itemService
     * @param RecipeRepository $recipeRepository
     * @param RegistryService $registryService
     */
    public function __construct(
        CraftingCategoryService $craftingCategoryService,
        EntityManager $entityManager,
        ItemService $itemService,
        RecipeRepository $recipeRepository,
        RegistryService $registryService
    ) {
        parent::__construct($entityManager);

        $this->craftingCategoryService = $craftingCategoryService;
        $this->itemService = $itemService;
        $this->recipeRepository = $recipeRepository;
        $this->registryService = $registryService;
    }

    /**
     * Imports the items.
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @throws ImportException
     */
    public function import(ExportCombination $exportCombination, DatabaseCombination $databaseCombination): void
    {
        $newRecipes = $this->getRecipesFromExportCombination($exportCombination);
        $existingRecipes = $this->getExistingRecipes($newRecipes);
        $persistedRecipes = $this->persistEntities($newRecipes, $existingRecipes);
        $this->assignEntitiesToCollection($persistedRecipes, $databaseCombination->getRecipes());
    }

    /**
     * Returns the recipes from the specified combination.
     * @param ExportCombination $exportCombination
     * @return array|DatabaseRecipe[]
     * @throws ImportException
     */
    protected function getRecipesFromExportCombination(ExportCombination $exportCombination): array
    {
        $result = [];
        foreach ($exportCombination->getRecipeHashes() as $recipeHash) {
            $exportRecipe = $this->registryService->getRecipe($recipeHash);
            $databaseRecipe = $this->mapRecipe($exportRecipe);
            $result[$this->getIdentifier($databaseRecipe)] = $databaseRecipe;
        }
        return $result;
    }

    /**
     * Maps the export recipe to a database entity.
     * @param ExportRecipe $recipe
     * @return DatabaseRecipe
     * @throws ImportException
     */
    protected function mapRecipe(ExportRecipe $recipe): DatabaseRecipe
    {
        $result = new DatabaseRecipe(
            $recipe->getName(),
            $recipe->getMode(),
            $this->craftingCategoryService->getByName($recipe->getCraftingCategory())
        );
        $result->setCraftingTime($recipe->getCraftingTime());

        foreach ($recipe->getIngredients() as $index => $ingredient) {
            $result->getIngredients()->add($this->mapIngredient($result, $ingredient, $index + 1));
        }
        foreach ($recipe->getProducts() as $index => $product) {
            $result->getProducts()->add($this->mapProduct($result, $product, $index + 1));
        }

        return $result;
    }

    /**
     * Maps the export ingredient to a database entity.
     * @param DatabaseRecipe $recipe
     * @param Ingredient $ingredient
     * @param int $order
     * @return RecipeIngredient
     * @throws ImportException
     */
    protected function mapIngredient(DatabaseRecipe $recipe, Ingredient $ingredient, int $order): RecipeIngredient
    {
        $item = $this->itemService->getByTypeAndName($ingredient->getType(), $ingredient->getName());

        $result = new RecipeIngredient($recipe, $item);
        $result->setAmount($ingredient->getAmount())
               ->setOrder($order);
        return $result;
    }

    /**
     * Maps the export product to a database entity.
     * @param DatabaseRecipe $recipe
     * @param Product $product
     * @param int $order
     * @return RecipeProduct
     * @throws ImportException
     */
    protected function mapProduct(DatabaseRecipe $recipe, Product $product, int $order): RecipeProduct
    {
        $item = $this->itemService->getByTypeAndName($product->getType(), $product->getName());

        $result = new RecipeProduct($recipe, $item);
        $result->setAmountMin($product->getAmountMin())
               ->setAmountMax($product->getAmountMax())
               ->setProbability($product->getProbability())
               ->setOrder($order);
        return $result;
    }

    /**
     * Returns the already existing entities of thew specified recipies.
     * @param array|DatabaseRecipe[] $recipes
     * @return array|DatabaseRecipe[]
     */
    protected function getExistingRecipes(array $recipes): array
    {
        $recipeNames = array_map(function (DatabaseRecipe $recipe): string {
            return $recipe->getName();
        }, $recipes);
        $recipeData = $this->recipeRepository->findDataByNames($recipeNames);
        $recipeIds = array_map(function (RecipeData $recipeData): int {
            return $recipeData->getId();
        }, $recipeData);

        $result = [];
        foreach ($this->recipeRepository->findByIds($recipeIds) as $recipe) {
            $result[$this->getIdentifier($recipe)] = $recipe;
        }
        return $result;
    }

    /**
     * Returns the identifier of the recipe.
     * @param DatabaseRecipe $recipe
     * @return string
     */
    protected function getIdentifier(DatabaseRecipe $recipe): string
    {
        return EntityUtils::calculateHashOfArray([
            $recipe->getName(),
            $recipe->getMode(),
            $recipe->getCraftingTime(),
            $recipe->getCraftingCategory()->getName(),
            array_map(function (RecipeIngredient $ingredient): array {
                return [
                    $ingredient->getItem()->getType(),
                    $ingredient->getItem()->getName(),
                    $ingredient->getAmount()
                ];
            }, $recipe->getOrderedIngredients()->toArray()),
            array_map(function (RecipeProduct $product): array {
                return [
                    $product->getItem()->getType(),
                    $product->getItem()->getName(),
                    $product->getAmountMin(),
                    $product->getAmountMax(),
                    $product->getProbability(),
                ];
            }, $recipe->getOrderedProducts()->toArray())
        ]);
    }

    /**
     * Persists the specified entity.
     * @param DatabaseRecipe $recipe
     * @throws ORMException
     */
    protected function persistEntity($recipe): void
    {
        foreach ($recipe->getIngredients() as $ingredient) {
            $this->entityManager->persist($ingredient);
        }
        foreach ($recipe->getProducts() as $product) {
            $this->entityManager->persist($product);
        }
        $this->entityManager->persist($recipe);
    }
}
