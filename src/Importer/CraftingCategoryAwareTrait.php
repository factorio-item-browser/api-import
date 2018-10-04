<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;

/**
 * The trait adding the awareness of crafting categories.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
trait CraftingCategoryAwareTrait
{
    /**
     * The repository of the crafting categories.
     * @var CraftingCategoryRepository
     */
    protected $craftingCategoryRepository;

    /**
     * The cached crafting categories.
     * @var array|CraftingCategory[]
     */
    protected $craftingCategoryCache = [];

    /**
     * Fetches the crafting category with the specified name from the database.
     * @param string $name
     * @return CraftingCategory
     * @throws ImportException
     */
    protected function getCraftingCategory(string $name): CraftingCategory
    {
        if (!isset($this->craftingCategoryCache[$name])) {
            $craftingCategories = $this->craftingCategoryRepository->findByNames([$name]);
            $craftingCategory = array_shift($craftingCategories);
            if (!$craftingCategory instanceof CraftingCategory) {
                throw new ImportException('Missing crafting category: ' . $name);
            }
            $this->craftingCategoryCache[$name] = $craftingCategory;
        }
        return $this->craftingCategoryCache[$name];
    }
}
