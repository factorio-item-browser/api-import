<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The trait adding the awareness of crafting categories.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
trait ItemAwareTrait
{
    /**
     * The repository of the crafting categories.
     * @var ItemRepository
     */
    protected $itemRepository;

    /**
     * The cached crafting categories.
     * @var array|Item[]
     */
    protected $itemCache = [];

    /**
     * Fetches the crafting category with the specified name from the database.
     * @param string $type
     * @param string $name
     * @return Item
     * @throws ImportException
     */
    protected function getItem(string $type, string $name): Item
    {
        $key = EntityUtils::buildIdentifier([$type, $name]);
        if (!isset($this->itemCache[$key])) {
            $items = $this->itemRepository->findByTypesAndNames([$type => [$name]]);
            $item = array_shift($items);
            if (!$item instanceof Item) {
                throw new ImportException('Missing item: ' . $type . ' / ' . $name);
            }
            $this->itemCache[$key] = $item;
        }
        return $this->itemCache[$key];
    }
}
