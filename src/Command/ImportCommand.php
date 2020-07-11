<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use BluePsyduck\SymfonyProcessManager\ProcessManager;
use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
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
    protected int $numberOfParallelProcesses;

    /**
     * @param CombinationRepository $combinationRepository
     * @param Console $console
     * @param ExportDataService $exportDataService
     * @param array<string, ImporterInterface> $importers
     * @param int $importChunkSize
     * @param int $numberOfParallelProcesses
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        Console $console,
        ExportDataService $exportDataService,
        array $importers,
        int $importChunkSize,
        int $numberOfParallelProcesses
    ) {
        parent::__construct($combinationRepository, $console, $exportDataService);

        $this->importers = $importers;
        $this->chunkSize = $importChunkSize;
        $this->numberOfParallelProcesses = $numberOfParallelProcesses;
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

        $processManager = $this->createProcessManager();
        for ($i = 0; $i < $numberOfChunks; ++$i) {
            $processManager->addProcess($this->createSubProcess($combination, $name, $i));
        }
        $processManager->waitForAllProcesses();
    }

    protected function createProcessManager(): ProcessManagerInterface
    {
        $processManager = new ProcessManager($this->numberOfParallelProcesses);
        $processManager->setProcessStartCallback(function (ImportCommandProcess $process): void {
            $this->handleProcessStart($process);
        });
        $processManager->setProcessFinishCallback(function (ImportCommandProcess $process): void {
            $this->handleProcessFinish($process);
        });
        return $processManager;
    }

    /**
     * @param ImportCommandProcess<string> $process
     */
    protected function handleProcessStart(ImportCommandProcess $process): void
    {
        static $index = 0;
        ++$index;
        $this->console->writeAction("Processing batch {$index}");
    }

    /**
     * @param ImportCommandProcess<string> $process
     * @throws CommandFailureException
     */
    protected function handleProcessFinish(ImportCommandProcess $process): void
    {
        if ($process->isSuccessful()) {
            $this->console->writeData($process->getOutput());
        } else {
            throw new CommandFailureException($process->getOutput());
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

    protected function cleanup(): void
    {
        $this->console->writeStep('Cleaning up');
        foreach (array_reverse($this->importers) as $name => $importer) {
            $this->console->writeAction("Importer: {$name}");
            $importer->cleanup();
        }
    }
}
