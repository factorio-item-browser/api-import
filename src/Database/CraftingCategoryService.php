<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Database;

use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Exception\MissingEntityException;

/**
 * The service providing the crafting categories.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CraftingCategoryService
{
    /**
     * The repository of the crafting categories.
     * @var CraftingCategoryRepository
     */
    protected $craftingCategoryRepository;

    /**
     * The cache of the service.
     * @var array|CraftingCategory[]
     */
    protected $cache = [];

    /**
     * Initializes the service.
     * @param CraftingCategoryRepository $craftingCategoryRepository
     */
    public function __construct(CraftingCategoryRepository $craftingCategoryRepository)
    {
        $this->craftingCategoryRepository = $craftingCategoryRepository;
    }

    /**
     * Returns the crafting category with the specified name.
     * @param string $name
     * @return CraftingCategory
     * @throws MissingEntityException
     */
    public function getByName(string $name): CraftingCategory
    {
        if (!isset($this->cache[$name])) {
            $this->cache[$name] = $this->fetchByName($name);
        }
        return $this->cache[$name];
    }

    /**
     * Fetches the crafting category with the specified name.
     * @param string $name
     * @return CraftingCategory
     * @throws MissingEntityException
     */
    protected function fetchByName(string $name): CraftingCategory
    {
        $craftingCategories = $this->craftingCategoryRepository->findByNames([$name]);
        $result = array_shift($craftingCategories);
        if (!$result instanceof CraftingCategory) {
            throw new MissingEntityException(CraftingCategory::class, $name);
        }
        return $result;
    }
}
