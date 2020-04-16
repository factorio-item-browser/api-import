<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\MissingCraftingCategoryException;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The importer of the crafting categories.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CraftingCategoryImporter implements ImporterInterface
{
    /**
     * The crafting category repository.
     * @var CraftingCategoryRepository
     */
    protected $craftingCategoryRepository;

    /**
     * The id calculator.
     * @var IdCalculator
     */
    protected $idCalculator;

    /**
     * The crafting categories by their name.
     * @var array|CraftingCategory[]
     */
    protected $craftingCategories = [];

    /**
     * Initializes the importer.
     * @param CraftingCategoryRepository $craftingCategoryRepository
     * @param IdCalculator $idCalculator
     */
    public function __construct(CraftingCategoryRepository $craftingCategoryRepository, IdCalculator $idCalculator)
    {
        $this->craftingCategoryRepository = $craftingCategoryRepository;
        $this->idCalculator = $idCalculator;
    }

    /**
     * Prepares the data provided for the other importers.
     * @param ExportData $exportData
     */
    public function prepare(ExportData $exportData): void
    {
        $this->craftingCategories = [];

        $ids = [];
        foreach ($exportData->getCombination()->getMachines() as $machine) {
            foreach ($machine->getCraftingCategories() as $name) {
                $craftingCategory = $this->create($name);
                $ids[] = $craftingCategory->getId();

                $this->add($craftingCategory);
            }
        }
        foreach ($exportData->getCombination()->getRecipes() as $recipe) {
            $craftingCategory = $this->create($recipe->getCraftingCategory());
            $ids[] = $craftingCategory->getId();

            $this->add($craftingCategory);
        }

        foreach ($this->craftingCategoryRepository->findByIds($ids) as $craftingCategory) {
            $this->add($craftingCategory);
        }
    }

    /**
     * Creates a crafting category entity.
     * @param string $name
     * @return CraftingCategory
     */
    protected function create(string $name): CraftingCategory
    {
        $craftingCategory = new CraftingCategory();
        $craftingCategory->setName($name);

        $craftingCategory->setId($this->idCalculator->calculateIdOfCraftingCategory($craftingCategory));
        return $craftingCategory;
    }

    /**
     * Adds a crafting category to the local properties of the importer.
     * @param CraftingCategory $craftingCategory
     */
    protected function add(CraftingCategory $craftingCategory): void
    {
        $this->craftingCategories[$craftingCategory->getName()] = $craftingCategory;
    }

    /**
     * Returns the crafting category with the specified name.
     * @param string $name
     * @return CraftingCategory
     * @throws ImportException
     */
    public function getByName(string $name): CraftingCategory
    {
        if (isset($this->craftingCategories[$name])) {
            return $this->craftingCategories[$name];
        }

        throw new MissingCraftingCategoryException($name);
    }

    /**
     * Actually parses the data, having access to data provided by other importers.
     * @param ExportData $exportData
     */
    public function parse(ExportData $exportData): void
    {
    }

    /**
     * Persists the parsed data to the combination.
     * @param EntityManagerInterface $entityManager
     * @param Combination $combination
     */
    public function persist(EntityManagerInterface $entityManager, Combination $combination): void
    {
        foreach ($this->craftingCategories as $craftingCategory) {
            $entityManager->persist($craftingCategory);
        }
    }

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void
    {
        $this->craftingCategoryRepository->removeOrphans();
    }
}
