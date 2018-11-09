<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Database;

use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Exception\MissingEntityException;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The service providing the items.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ItemService
{
    /**
     * The repository of the items.
     * @var ItemRepository
     */
    protected $itemRepository;

    /**
     * The cache of the service.
     * @var array|Item[]
     */
    protected $cache = [];

    /**
     * Initializes the service.
     * @param ItemRepository $itemRepository
     */
    public function __construct(ItemRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    /**
     * Returns the item with the specified type and name.
     * @param string $type
     * @param string $name
     * @return Item
     * @throws MissingEntityException
     */
    public function getByTypeAndName(string $type, string $name): Item
    {
        $key = EntityUtils::buildIdentifier([$type, $name]);
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->fetchByTypeAndName($type, $name);
        }
        return $this->cache[$key];
    }

    /**
     * Fetches the item with the specified name.
     * @param string $type
     * @param string $name
     * @return Item
     * @throws MissingEntityException
     */
    protected function fetchByTypeAndName(string $type, string $name): Item
    {
        $items = $this->itemRepository->findByTypesAndNames([$type => [$name]]);
        $result = array_shift($items);
        if (!$result instanceof Item) {
            throw new MissingEntityException(Item::class, $name);
        }
        return $result;
    }
}
