<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Recipe as DatabaseRecipe;
use FactorioItemBrowser\Api\Database\Entity\RecipeIngredient as DatabaseIngredient;
use FactorioItemBrowser\Api\Database\Entity\RecipeProduct as DatabaseProduct;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\DataCollector;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\Entity\Recipe\Ingredient as ExportIngredient;
use FactorioItemBrowser\ExportData\Entity\Recipe\Product as ExportProduct;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the recipes.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractEntityImporter<ExportRecipe, DatabaseRecipe>
 */
class RecipeImporter extends AbstractEntityImporter
{
    protected DataCollector $dataCollector;
    protected IdCalculator $idCalculator;
    protected Validator $validator;

    public function __construct(
        DataCollector $dataCollector,
        EntityManagerInterface $entityManager,
        IdCalculator $idCalculator,
        RecipeRepository $repository,
        Validator $validator
    ) {
        parent::__construct($entityManager, $repository);

        $this->dataCollector = $dataCollector;
        $this->idCalculator = $idCalculator;
        $this->validator = $validator;
    }

    protected function getCollectionFromCombination(Combination $combination): Collection
    {
        return $combination->getRecipes();
    }

    protected function prepareImport(Combination $combination, ExportData $exportData, int $offset, int $limit): void
    {
        $this->dataCollector->setCombination($combination);
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        foreach ($exportData->getRecipes() as $recipe) {
            /* @var ExportRecipe $recipe */
            $this->dataCollector->addCraftingCategoryName($recipe->craftingCategory);
            foreach ($recipe->ingredients as $ingredient) {
                $this->dataCollector->addItem($ingredient->type, $ingredient->name);
            }
            foreach ($recipe->products as $product) {
                $this->dataCollector->addItem($product->type, $product->name);
            }

            yield $recipe;
        }
    }

    /**
     * @param ExportRecipe $exportRecipe
     * @return DatabaseRecipe
     * @throws ImportException
     */
    protected function createDatabaseEntity($exportRecipe)
    {
        $databaseRecipe = new DatabaseRecipe();

        $databaseRecipe->setName($exportRecipe->name)
                       ->setMode($exportRecipe->mode)
                       ->setCraftingCategory(
                           $this->dataCollector->getCraftingCategory($exportRecipe->craftingCategory),
                       )
                       ->setCraftingTime($exportRecipe->craftingTime);

        $this->mapIngredients($exportRecipe, $databaseRecipe);
        $this->mapProducts($exportRecipe, $databaseRecipe);

        $this->validator->validateRecipe($databaseRecipe);
        $databaseRecipe->setId($this->idCalculator->calculateIdOfRecipe($databaseRecipe));
        return $databaseRecipe;
    }

    /**
     * @param ExportRecipe $exportRecipe
     * @param DatabaseRecipe $databaseRecipe
     * @throws ImportException
     */
    protected function mapIngredients(ExportRecipe $exportRecipe, DatabaseRecipe $databaseRecipe): void
    {
        foreach ($exportRecipe->ingredients as $index => $exportIngredient) {
            $databaseIngredient = $this->mapIngredient($exportIngredient);
            $databaseIngredient->setRecipe($databaseRecipe)
                               ->setOrder($index);
            $databaseRecipe->getIngredients()->add($databaseIngredient);
        }
    }

    /**
     * @param ExportIngredient $exportIngredient
     * @return DatabaseIngredient
     * @throws ImportException
     */
    protected function mapIngredient(ExportIngredient $exportIngredient): DatabaseIngredient
    {
        $item = $this->dataCollector->getItem($exportIngredient->type, $exportIngredient->name);

        $databaseIngredient = new DatabaseIngredient();
        $databaseIngredient->setItem($item)
                           ->setAmount($exportIngredient->amount);
        return $databaseIngredient;
    }

    /**
     * @param ExportRecipe $exportRecipe
     * @param DatabaseRecipe $databaseRecipe
     * @throws ImportException
     */
    protected function mapProducts(ExportRecipe $exportRecipe, DatabaseRecipe $databaseRecipe): void
    {
        foreach ($exportRecipe->products as $index => $exportProduct) {
            $databaseProduct = $this->mapProduct($exportProduct);
            $databaseProduct->setRecipe($databaseRecipe)
                            ->setOrder($index);
            $databaseRecipe->getProducts()->add($databaseProduct);
        }
    }

    /**
     * @param ExportProduct $exportProduct
     * @return DatabaseProduct
     * @throws ImportException
     */
    protected function mapProduct(ExportProduct $exportProduct): DatabaseProduct
    {
        $item = $this->dataCollector->getItem($exportProduct->type, $exportProduct->name);

        $databaseProduct = new DatabaseProduct();
        $databaseProduct->setItem($item)
                        ->setAmountMin($exportProduct->amountMin)
                        ->setAmountMax($exportProduct->amountMax)
                        ->setProbability($exportProduct->probability);
        return $databaseProduct;
    }
}
