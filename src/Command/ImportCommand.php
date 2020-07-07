<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Exception;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Exception\CommandFailureException;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use FactorioItemBrowser\Api\Import\Process\ImportCommandProcess;
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
     * @var array<string, ImporterInterface>|ImporterInterface[]
     */
    protected array $importers;
    protected int $chunkSize;

    /**
     * @param CombinationRepository $combinationRepository
     * @param Console $console
     * @param ExportDataService $exportDataService
     * @param array<string, ImporterInterface> $importers
     * @param int $importChunkSize
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        Console $console,
        ExportDataService $exportDataService,
        array $importers,
        int $importChunkSize
    ) {
        parent::__construct($combinationRepository, $console, $exportDataService);

        $this->importers = $importers;
        $this->chunkSize = $importChunkSize;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName(CommandName::IMPORT);
        $this->setDescription('Imports the main data of a combination.');
    }

    /**
     * @param ExportData $exportData
     * @param Combination $combination
     * @throws Exception
     */
    protected function import(ExportData $exportData, Combination $combination): void
    {
        $this->console->writeHeadline(sprintf('Importing combination %s', $combination->getId()->toString()));

        foreach ($this->importers as $name => $importer) {
            $this->executeImporter($name, $importer, $exportData, $combination);
        }
        $this->cleanup();

        $this->console->writeStep('Done.');
    }

    /**
     * @param string $name
     * @param ImporterInterface $importer
     * @param ExportData $exportData
     * @param Combination $combination
     * @throws CommandFailureException
     */
    protected function executeImporter(
        string $name,
        ImporterInterface $importer,
        ExportData $exportData,
        Combination $combination
    ): void {
        $this->console->writeStep('Executing importer: ' . $name);

        $count = $importer->count($exportData);
        $numberOfChunks = ceil($count / $this->chunkSize);
        $this->console->writeMessage("Will process {$count} datasets in {$numberOfChunks} chunks");

        $this->console->writeAction('Preparing importer');
        $importer->prepare($combination);

        for ($i = 0; $i < $numberOfChunks; ++$i) {
            $this->console->writeAction("Processing batch {$i}");
            $process = $this->createSubProcess($combination, $name, $i);
            $this->runSubProcess($process);
        }
    }

    /**
     * @param Combination $combination
     * @param string $part
     * @param int $chunk
     * @return ImportCommandProcess<string>
     */
    protected function createSubProcess(Combination $combination, string $part, int $chunk): ImportCommandProcess
    {
        return new ImportCommandProcess(CommandName::IMPORT_PART, $combination, [
            $part,
            (string) ($chunk * $this->chunkSize),
            (string) $this->chunkSize,
        ]);
    }

    /**
     * @param ImportCommandProcess<string> $process
     * @throws CommandFailureException
     */
    protected function runSubProcess(ImportCommandProcess $process): void
    {
        $process->run(function ($type, $data): void {
            $this->console->writeData($data);
        });

        if (!$process->isSuccessful()) {
            throw new CommandFailureException($process->getOutput());
        }
    }

    protected function cleanup(): void
    {
        $this->console->writeStep('Cleaning up');
        foreach (array_reverse($this->importers) as $name => $importer) {
            $this->console->writeAction("Importer: {$name}");
            $importer->cleanup();
        }
    }
}
