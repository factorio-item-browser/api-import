<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Item as DatabaseItem;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The importer of the items.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ItemImporter implements ImporterInterface
{
    /**
     * The id calculator.
     * @var IdCalculator
     */
    protected $idCalculator;

    /**
     * The item repository.
     * @var ItemRepository
     */
    protected $itemRepository;

    /**
     * The items.
     * @var array|DatabaseItem[]
     */
    protected $items = [];

    /**
     * The items by their type and name.
     * @var array|DatabaseItem[]
     */
    protected $itemsByTypeAndName = [];

    /**
     * Initializes the importer.
     * @param IdCalculator $idCalculator
     * @param ItemRepository $itemRepository
     */
    public function __construct(IdCalculator $idCalculator, ItemRepository $itemRepository)
    {
        $this->idCalculator = $idCalculator;
        $this->itemRepository = $itemRepository;
    }

    /**
     * Prepares the data provided for the other importers.
     * @param ExportData $exportData
     */
    public function prepare(ExportData $exportData): void
    {
        $this->items = [];
        $this->itemsByTypeAndName = [];

        $itemIds = [];
        foreach ($exportData->getCombination()->getItems() as $exportItem) {
            $databaseItem = $this->map($exportItem);
            $this->add($databaseItem);
            $itemIds[] = $databaseItem->getId();
        }

        $existingItems = $this->itemRepository->findByIds($itemIds);
        foreach ($existingItems as $item) {
            $this->add($item);
        }
    }

    /**
     * Actually parses the data, having access to data provided by other importers.
     * @param ExportData $exportData
     */
    public function parse(ExportData $exportData): void
    {
    }

    /**
     * Maps the export item to a database one.
     * @param ExportItem $exportItem
     * @return DatabaseItem
     */
    protected function map(ExportItem $exportItem): DatabaseItem
    {
        $databaseItem = new DatabaseItem();
        $databaseItem->setType($exportItem->getType())
                     ->setName($exportItem->getName());

        $databaseItem->setId($this->idCalculator->calculateIdOfItem($databaseItem));
        return $databaseItem;
    }

    /**
     * Adds an item to the local properties of the importer.
     * @param DatabaseItem $databaseItem
     */
    protected function add(DatabaseItem $databaseItem): void
    {
        $this->items[$databaseItem->getId()->toString()] = $databaseItem;
        $this->itemsByTypeAndName[$databaseItem->getType()][$databaseItem->getName()] = $databaseItem;
    }

    /**
     * Returns a parsed item from the importer.
     * @param string $type
     * @param string $name
     * @return DatabaseItem
     */
    public function getByTypeAndName(string $type, string $name): DatabaseItem
    {
        return $this->itemsByTypeAndName[$type][$name];
    }

    /**
     * Persists the parsed data to the combination.
     * @param EntityManagerInterface $entityManager
     * @param Combination $combination
     */
    public function persist(EntityManagerInterface $entityManager, Combination $combination): void
    {
        $combination->getItems()->clear();
        foreach ($this->items as $item) {
            $entityManager->persist($item);
            $combination->getItems()->add($item);
        }
    }

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void
    {
        $this->itemRepository->removeOrphans();
    }
}
