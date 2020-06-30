<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\NewImporter;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Item as DatabaseItem;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the items.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractImporter<ExportItem, DatabaseItem>
 */
class ItemImporter extends AbstractImporter
{
    protected IdCalculator $idCalculator;

    public function __construct(
        EntityManagerInterface $entityManager,
        IdCalculator $idCalculator,
        ItemRepository $itemRepository
    ) {
        parent::__construct($entityManager, $itemRepository);
        $this->idCalculator = $idCalculator;
    }

    protected function getCollectionFromCombination(Combination $combination): Collection
    {
        return $combination->getItems();
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        yield from $exportData->getCombination()->getItems();
    }

    /**
     * @param ExportItem $entity
     * @return DatabaseItem
     */
    protected function createDatabaseEntity($entity): DatabaseItem
    {
        $databaseItem = new DatabaseItem();
        $databaseItem->setType($entity->getType())
                     ->setName(substr(trim($entity->getName()), 0, 255));

        $databaseItem->setId($this->idCalculator->calculateIdOfItem($databaseItem));
        return $databaseItem;
    }
}
