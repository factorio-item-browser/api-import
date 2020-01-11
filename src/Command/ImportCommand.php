<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;

/**
 * The command to import all non-critical data.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ImportCommand extends AbstractImportCommand
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
     * @param Console $console
     * @param EntityManagerInterface $entityManager
     * @param ExportDataService $exportDataService
     * @param ImporterInterface[] $importers
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        Console $console,
        EntityManagerInterface $entityManager,
        ExportDataService $exportDataService,
        array $importers
    ) {
        parent::__construct($combinationRepository, $console, $exportDataService);

        $this->entityManager = $entityManager;
        $this->importers = $importers;
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName(CommandName::IMPORT);
        $this->setDescription('Imports the main data of a combination.');
    }

    /**
     * Returns a label describing what the import is doing.
     * @return string
     */
    protected function getLabel(): string
    {
        return 'Processing the main data of the combination';
    }

    /**
     * Imports the export data into the combination.
     * @param ExportData $exportData
     * @param Combination $combination
     */
    protected function import(ExportData $exportData, Combination $combination): void
    {
        $this->console->writeAction('Preparing importers');
        foreach ($this->importers as $importer) {
            $importer->prepare($exportData);
        }

        $this->console->writeAction('Parsing the export data');
        foreach ($this->importers as $importer) {
            $importer->parse($exportData);
        }

        $this->console->writeAction('Persisting the parsed data');
        foreach ($this->importers as $importer) {
            $importer->persist($this->entityManager, $combination);
        }
        $this->entityManager->flush();

        $this->console->writeAction('Cleaning up obsolete data');
        foreach ($this->importers as $importer) {
            $importer->cleanup();
        }
        $this->entityManager->flush();
    }
}
