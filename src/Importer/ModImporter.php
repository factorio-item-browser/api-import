<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the mods.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractEntityImporter<ExportMod, DatabaseMod>
 */
class ModImporter extends AbstractEntityImporter
{
    protected IdCalculator $idCalculator;
    protected Validator $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        IdCalculator $idCalculator,
        ModRepository $repository,
        Validator $validator
    ) {
        parent::__construct($entityManager, $repository);

        $this->idCalculator = $idCalculator;
        $this->validator = $validator;
    }

    protected function getCollectionFromCombination(Combination $combination): Collection
    {
        return $combination->getMods();
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        yield from $exportData->getMods();
    }

    /**
     * @param ExportMod $exportMod
     * @return DatabaseMod
     */
    protected function createDatabaseEntity($exportMod): object
    {
        $databaseMod = new DatabaseMod();
        $databaseMod->setName($exportMod->name)
                    ->setVersion($exportMod->version)
                    ->setAuthor($exportMod->author);

        $this->validator->validateMod($databaseMod);
        $databaseMod->setId($this->idCalculator->calculateIdOfMod($databaseMod));
        return $databaseMod;
    }
}
