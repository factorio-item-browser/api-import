<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use FactorioItemBrowser\Api\Database\Entity\Item as DatabaseItem;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Registry\EntityRegistry;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;
use FactorioItemBrowserTest\Api\Import\Exception\ImportException;

/**
 * The importer of the items.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ItemImporter implements ImporterInterface
{
    /**
     * The entity Manager.
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * The registry of the items.
     * @var EntityRegistry
     */
    protected $itemRegistry;

    /**
     * The repository of the items.
     * @var ItemRepository
     */
    protected $itemRepository;

    /**
     * Initializes the importer.
     * @param EntityManager $entityManager
     * @param EntityRegistry $itemRegistry
     * @param ItemRepository $itemRepository
     */
    public function __construct(
        EntityManager $entityManager,
        EntityRegistry $itemRegistry,
        ItemRepository $itemRepository
    ) {
        $this->entityManager = $entityManager;
        $this->itemRegistry = $itemRegistry;
        $this->itemRepository = $itemRepository;
    }

    /**
     * Imports the items.
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @throws ImportException
     */
    public function import(ExportCombination $exportCombination, DatabaseCombination $databaseCombination): void
    {
        $newItems = $this->getItemsFromCombination($exportCombination);
        $existingItems = $this->getExistingItems($newItems);
        $persistedItems = $this->persistItems($newItems, $existingItems);
        $this->assignItemsToCombination($persistedItems, $databaseCombination);
    }

    /**
     * Returns the items from the specified combination.
     * @param ExportCombination $combination
     * @return array|DatabaseItem[]
     * @throws ImportException
     */
    protected function getItemsFromCombination(ExportCombination $combination): array
    {
        $result = [];
        foreach ($combination->getItemHashes() as $itemHash) {
            $exportItem = $this->itemRegistry->get($itemHash);
            if (!$exportItem instanceof ExportItem)  {
                throw new ImportException('Unable to find item with hash ' . $itemHash);
            }

            $databaseItem = $this->mapItem($exportItem);
            $result[$this->getIdentifier($databaseItem)] = $databaseItem;
        }
        return $result;
    }

    /**
     * Maps the specified export item to a database entity.
     * @param ExportItem $item
     * @return DatabaseItem
     */
    protected function mapItem(ExportItem $item): DatabaseItem
    {
        return new DatabaseItem($item->getType(), $item->getName());
    }

    /**
     * Returns the already existing entities to the specified items.
     * @param array|DatabaseItem[] $items
     * @return array|DatabaseItem[]
     */
    protected function getExistingItems(array $items): array
    {
        $itemsByTypeAndName = [];
        foreach ($items as $item) {
            $itemsByTypeAndName[$item->getType()][] = $item->getName();
        }

        $result = [];
        foreach ($this->itemRepository->findByTypesAndNames($itemsByTypeAndName) as $item) {
            $result[$this->getIdentifier($item)] = $item;
        }
        return $result;
    }

    /**
     * Persists the specified items into the database.
     * @param array|DatabaseItem[] $newItems
     * @param array|DatabaseItem[] $existingItems
     * @return array|DatabaseItem[]
     * @throws ImportException
     */
    protected function persistItems(array $newItems, array $existingItems): array
    {
        $result = [];
        try {
            foreach ($newItems as $key => $newItem) {
                if (isset($existingItems[$key])) {
                    $result[$key] = $existingItems[$key];
                } else {
                    $this->entityManager->persist($newItem);
                    $result[$key] = $newItem;
                }
            }
            $this->entityManager->flush();
        } catch (ORMException $e) {
            throw new ImportException('Failed to persist items.', 0, $e);
        }
        return $result;
    }

    /**
     * Assigns the items to the database combination.
     * @param array|DatabaseItem[] $items
     * @param DatabaseCombination $databaseCombination
     */
    protected function assignItemsToCombination(array $items, DatabaseCombination $databaseCombination): void
    {
        $databaseCombination->getItems()->clear();
        foreach ($items as $item) {
            $databaseCombination->getItems()->add($item);
        }
    }

    /**
     * Returns the identifier for the specified item.
     * @param DatabaseItem $item
     * @return string
     */
    protected function getIdentifier(DatabaseItem $item): string
    {
        return EntityUtils::buildIdentifier([$item->getType(), $item->getName()]);
    }
}
