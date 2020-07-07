<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer of the crafting categories.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractEntityImporter<string, CraftingCategory>
 */
class CraftingCategoryImporter extends AbstractEntityImporter
{
    protected IdCalculator $idCalculator;
    protected Validator $validator;

    public function __construct(
        CraftingCategoryRepository $repository,
        EntityManagerInterface $entityManager,
        IdCalculator $idCalculator,
        Validator $validator
    ) {
        parent::__construct($entityManager, $repository);

        $this->idCalculator = $idCalculator;
        $this->validator = $validator;
    }

    protected function getCollectionFromCombination(Combination $combination): Collection
    {
        // Crafting categories do not get assigned directly to the combination.
        return new ArrayCollection();
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        $seenCraftingCategories = [];

        foreach ($exportData->getCombination()->getRecipes() as $recipe) {
            $craftingCategory = $recipe->getCraftingCategory();
            if (!isset($seenCraftingCategories[$craftingCategory])) {
                $seenCraftingCategories[$craftingCategory] = true;
                yield $craftingCategory;
            }
        }

        foreach ($exportData->getCombination()->getMachines() as $machine) {
            foreach ($machine->getCraftingCategories() as $craftingCategory) {
                if (!isset($seenCraftingCategories[$craftingCategory])) {
                    $seenCraftingCategories[$craftingCategory] = true;
                    yield $craftingCategory;
                }
            }
        }
    }

    /**
     * @param string $name
     * @return CraftingCategory
     */
    protected function createDatabaseEntity($name): CraftingCategory
    {
        $craftingCategory = new CraftingCategory();
        $craftingCategory->setName($name);

        $this->validator->validateCraftingCategory($craftingCategory);
        $craftingCategory->setId($this->idCalculator->calculateIdOfCraftingCategory($craftingCategory));
        return $craftingCategory;
    }
}
