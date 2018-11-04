<?php

namespace FactorioItemBrowser\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;

/**
 * The importer of the crafting categories.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CraftingCategoryImporter extends AbstractImporter implements CombinationImporterInterface
{
    /**
     * The repository of the crafting categories.
     * @var CraftingCategoryRepository
     */
    protected $craftingCategoryRepository;

    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * CraftingCategoryImporter constructor.
     * @param CraftingCategoryRepository $craftingCategoryRepository
     * @param EntityManager $entityManager
     * @param RegistryService $registryService
     */
    public function __construct(
        CraftingCategoryRepository $craftingCategoryRepository,
        EntityManager $entityManager,
        RegistryService $registryService
    ) {
        parent::__construct($entityManager);
        $this->craftingCategoryRepository = $craftingCategoryRepository;
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
        $newCraftingCategories = array_merge(
            $this->getCraftingCategoriesFromMachineHashes($exportCombination->getMachineHashes()),
            $this->getCraftingCategoriesFromRecipeHashes($exportCombination->getRecipeHashes())
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
            $machine = $this->registryService->getMachine($machineHash);
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
    protected function getCraftingCategoriesFromRecipeHashes(array $recipeHashes): array
    {
        $result = [];
        foreach ($recipeHashes as $recipeHash) {
            $recipe = $this->registryService->getRecipe($recipeHash);
            $craftingCategory = $this->createCraftingCategory($recipe->getCraftingCategory());
            $result[$this->getIdentifier($craftingCategory)] = $craftingCategory;
        }
        return $result;
    }

    /**
     * Creates an entity for the specified crafting category.
     * @param string $name
     * @return CraftingCategory
     */
    protected function createCraftingCategory(string $name): CraftingCategory
    {
        return new CraftingCategory($name);
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
