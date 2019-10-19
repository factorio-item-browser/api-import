<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;

/**
 * The command to import all non-critical data.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ImportCommand extends AbstractCombinationImportCommand
{
    /**
     * The entity manager.
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * The importers.
     * @var ImporterInterface[]
     */
    protected $importers;

    /**
     * Initializes the command.
     * @param CombinationRepository $combinationRepository
     * @param EntityManagerInterface $entityManager
     * @param ExportDataService $exportDataService
     * @param ImporterInterface[] $importers
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        EntityManagerInterface $entityManager,
        ExportDataService $exportDataService,
        array $importers
    )
    {
        parent::__construct($combinationRepository, $exportDataService);
        $this->entityManager = $entityManager;
        $this->importers = $importers;
    }

    /**
     * Imports the export data into the combination.
     * @param ExportData $exportData
     * @param Combination $combination
     */
    protected function import(ExportData $exportData, Combination $combination): void
    {
        foreach ($this->importers as $importer) {
            $importer->prepare($exportData);
        }

        foreach ($this->importers as $importer) {
            $importer->parse($exportData);
        }

        foreach ($this->importers as $importer) {
            $importer->persist($this->entityManager, $combination);
        }
        $this->entityManager->flush();

//        foreach ($this->importers as $importer) {
//            $importer->cleanup();
//        }
//        $this->entityManager->flush();
    }
}
