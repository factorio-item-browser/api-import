<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Helper;

use FactorioItemBrowser\Api\Database\Collection\NamesByTypes;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Exception\MissingCraftingCategoryException;
use FactorioItemBrowser\Api\Import\Exception\MissingItemException;

/**
 *
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class DataCollector
{
    protected CraftingCategoryRepository $craftingCategoryRepository;
    protected ItemRepository $itemRepository;
    protected Combination $combination;

    /**
     * @var array<string, bool>
     */
    protected array $craftingCategoryNames;

    protected NamesByTypes $itemTypesAndNames;

    /**
     * @var array<string, CraftingCategory>
     */
    protected array $craftingCategories;

    /**
     * @var array<string, array<string, Item>>|Item[][]
     */
    protected array $items = [];

    public function __construct(CraftingCategoryRepository $craftingCategoryRepository, ItemRepository $itemRepository)
    {
        $this->craftingCategoryRepository = $craftingCategoryRepository;
        $this->itemRepository = $itemRepository;

        $this->itemTypesAndNames = new NamesByTypes();
    }

    public function setCombination(Combination $combination): self
    {
        $this->combination = $combination;
        return $this;
    }

    public function addCraftingCategory(string $name): self
    {
        $this->craftingCategoryNames[$name] = true;
        return $this;
    }

    /**
     * @param string $name
     * @return CraftingCategory
     * @throws MissingCraftingCategoryException
     */
    public function getCraftingCategory(string $name): CraftingCategory
    {
        $this->fetchCraftingCategories();
        if (!isset($this->craftingCategories[$name])) {
            throw new MissingCraftingCategoryException($name);
        }
        return $this->craftingCategories[$name];
    }

    protected function fetchCraftingCategories(): void
    {
        if (count($this->craftingCategoryNames) > 0) {
            $craftingCategories = $this->craftingCategoryRepository->findByNames(
                array_keys($this->craftingCategoryNames),
            );
            foreach ($craftingCategories as $craftingCategory) {
                $this->craftingCategories[$craftingCategory->getName()] = $craftingCategory;
            }

            $this->craftingCategoryNames = [];
        }
    }

    public function addItem(string $type, string $name): self
    {
        $this->itemTypesAndNames->addName($type, $name);
        return $this;
    }

    /**
     * @param string $type
     * @param string $name
     * @return Item
     * @throws MissingItemException
     */
    public function getItem(string $type, string $name): Item
    {
        $this->fetchItems();
        if (!isset($this->items[$type][$name])) {
            throw new MissingItemException($type, $name);
        }
        return $this->items[$type][$name];
    }

    protected function fetchItems(): void
    {
        if (!$this->itemTypesAndNames->isEmpty()) {
            $items = $this->itemRepository->findByTypesAndNames($this->combination->getId(), $this->itemTypesAndNames);
            foreach ($items as $item) {
                $this->items[$item->getType()][$item->getName()] = $item;
            }

            $this->itemTypesAndNames = new NamesByTypes();
        }
    }
}
