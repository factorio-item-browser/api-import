<?php

namespace FactorioItemBrowser\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Item as DatabaseItem;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The importer of the items.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ItemImporter extends AbstractImporter implements CombinationImporterInterface
{
    /**
     * The repository of the items.
     * @var ItemRepository
     */
    protected $itemRepository;

    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * Initializes the importer.
     * @param EntityManager $entityManager
     * @param ItemRepository $itemRepository
     * @param RegistryService $registryService
     */
    public function __construct(
        EntityManager $entityManager,
        ItemRepository $itemRepository,
        RegistryService $registryService
    ) {
        parent::__construct($entityManager);
        $this->itemRepository = $itemRepository;
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
        $newItems = $this->getItemsFromCombination($exportCombination);
        $existingItems = $this->getExistingItems($newItems);
        $persistedItems = $this->persistEntities($newItems, $existingItems);
        $this->assignEntitiesToCollection($persistedItems, $databaseCombination->getItems());
    }

    /**
     * Returns the items from the specified combination.
     * @param ExportCombination $exportCombination
     * @return array|DatabaseItem[]
     * @throws ImportException
     */
    protected function getItemsFromCombination(ExportCombination $exportCombination): array
    {
        $result = [];
        foreach ($exportCombination->getItemHashes() as $itemHash) {
            $exportItem = $this->registryService->getItem($itemHash);
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
     * Returns the identifier for the specified item.
     * @param DatabaseItem $item
     * @return string
     */
    protected function getIdentifier(DatabaseItem $item): string
    {
        return EntityUtils::buildIdentifier([$item->getType(), $item->getName()]);
    }
}
