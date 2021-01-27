<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Item as DatabaseItem;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the items.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractEntityImporter<ExportItem, DatabaseItem>
 */
class ItemImporter extends AbstractEntityImporter
{
    protected IdCalculator $idCalculator;
    protected Validator $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        IdCalculator $idCalculator,
        ItemRepository $repository,
        Validator $validator
    ) {
        parent::__construct($entityManager, $repository);

        $this->idCalculator = $idCalculator;
        $this->validator = $validator;
    }

    protected function getCollectionFromCombination(Combination $combination): Collection
    {
        return $combination->getItems();
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        yield from $exportData->getItems();
    }

    /**
     * @param ExportItem $entity
     * @return DatabaseItem
     */
    protected function createDatabaseEntity($entity): DatabaseItem
    {
        $databaseItem = new DatabaseItem();
        $databaseItem->setType($entity->type)
                     ->setName($entity->name);

        $this->validator->validateItem($databaseItem);
        $databaseItem->setId($this->idCalculator->calculateIdOfItem($databaseItem));
        return $databaseItem;
    }
}
