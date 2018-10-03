<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\Registry\EntityRegistry;

/**
 * The importer of the crafting categories.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CraftingCategoryImporter extends AbstractImporter
{
    /**
     * The repository of the crafting categories.
     * @var CraftingCategoryRepository
     */
    protected $craftingCategoryRepository;

    /**
     * The registry of the machines.
     * @var EntityRegistry
     */
    protected $machineRegistry;

    /**
     * The registry of the recipes.
     * @var EntityRegistry
     */
    protected $recipeRegistry;

    /**
     * CraftingCategoryImporter constructor.
     * @param CraftingCategoryRepository $craftingCategoryRepository
     * @param EntityManager $entityManager
     * @param EntityRegistry $machineRegistry
     * @param EntityRegistry $recipeRegistry
     */
    public function __construct(
        CraftingCategoryRepository $craftingCategoryRepository,
        EntityManager $entityManager,
        EntityRegistry $machineRegistry,
        EntityRegistry $recipeRegistry
    ) {
        parent::__construct($entityManager);
        $this->craftingCategoryRepository = $craftingCategoryRepository;
        $this->machineRegistry = $machineRegistry;
        $this->recipeRegistry = $recipeRegistry;
    }

    /**
     * Imports the items.
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @throws ImportException
     */
    public function import(ExportCombination $exportCombination, DatabaseCombination $databaseCombination): void
    {
        $newCraftingCategories = array_merge(
            $this->getCraftingCategoriesFromMachineHashes($exportCombination->getMachineHashes()),
            $this->getCraftingCategoryFromRecipeHashes($exportCombination->getRecipeHashes())
        );
        $existingCraftingCategories = $this->getExistingCraftingCategories($newCraftingCategories);
        $this->persistEntities($newCraftingCategories, $existingCraftingCategories);
    }

    /**
     * Returns the crafting categories used by the specified machine hashes.
     * @param array|string[] $machineHashes
     * @return array|CraftingCategory[]
     * @throws ImportException
     */
    protected function getCraftingCategoriesFromMachineHashes(array $machineHashes): array
    {
        $result = [];
        foreach ($machineHashes as $machineHash) {
            $machine = $this->machineRegistry->get($machineHash);
            if (!$machine instanceof ExportMachine) {
                throw new UnknownHashException(EntityType::MACHINE, $machineHash);
            }

            foreach ($machine->getCraftingCategories() as $craftingCategoryName) {
                $craftingCategory = $this->createCraftingCategory($craftingCategoryName);
                $result[$this->getIdentifier($craftingCategory)] = $craftingCategory;
            }
        }
        return $result;
    }

    /**
     * Returns the crafting categories used by the specified recipe hashes.
     * @param array|string[] $recipeHashes
     * @return array|CraftingCategory[]
     * @throws ImportException
     */
    protected function getCraftingCategoryFromRecipeHashes(array $recipeHashes): array
    {
        $result = [];
        foreach ($recipeHashes as $recipeHash) {
            $recipe = $this->recipeRegistry->get($recipeHash);
            if (!$recipe instanceof ExportRecipe) {
                throw new UnknownHashException(EntityType::RECIPE, $recipeHash);
            }

            $craftingCategory = $this->createCraftingCategory($recipe->getCraftingCategory());
            $result[$this->getIdentifier($craftingCategory)] = $craftingCategory;
        }
        return $result;
    }

    /**
     * Creates an entity for the specified crafting category.
     * @param string $craftingCategory
     * @return CraftingCategory
     */
    protected function createCraftingCategory(string $craftingCategory): CraftingCategory
    {
        return new CraftingCategory($craftingCategory);
    }

    /**
     * Returns the already existing entities of the specified crafting categories.
     * @param array|CraftingCategory[] $craftingCategories
     * @return array|CraftingCategory[]
     */
    protected function getExistingCraftingCategories(array $craftingCategories): array
    {
        $result = [];
        foreach ($this->craftingCategoryRepository->findByNames(array_keys($craftingCategories)) as $craftingCategory) {
            $result[$this->getIdentifier($craftingCategory)] = $craftingCategory;
        }
        return $result;
    }

    /**
     * Returns the identifier for the specified crafting category.
     * @param CraftingCategory $craftingCategory
     * @return string
     */
    protected function getIdentifier(CraftingCategory $craftingCategory): string
    {
        return $craftingCategory->getName();
    }
}
