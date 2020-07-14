<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Exception;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Exception\MissingCombinationException;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The abstract class of commands importing data into a combination.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
abstract class AbstractImportCommand extends Command
{
    protected CombinationRepository $combinationRepository;
    protected Console $console;
    protected ExportDataService $exportDataService;

    public function __construct(
        CombinationRepository $combinationRepository,
        Console $console,
        ExportDataService $exportDataService
    ) {
        parent::__construct();

        $this->combinationRepository = $combinationRepository;
        $this->console = $console;
        $this->exportDataService = $exportDataService;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addArgument('combination', InputArgument::REQUIRED, 'The id of the combination to import.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->processInput($input);

            $combinationId = Uuid::fromString(strval($input->getArgument('combination')));
            $exportData = $this->exportDataService->loadExport($combinationId->toString());
            $combination = $this->combinationRepository->findById($combinationId);
            if ($combination === null) {
                throw new MissingCombinationException($combinationId);
            }

            $this->import($exportData, $combination);
            return 0;
        } catch (Exception $e) {
            $this->console->writeException($e);
            return 1;
        }
    }

    /**
     * Processes additional values from the input.
     * @param InputInterface $input
     */
    protected function processInput(InputInterface $input): void
    {
    }

    /**
     * Imports the export data into the combination.
     * @param ExportData $exportData
     * @param Combination $combination
     */
    abstract protected function import(ExportData $exportData, Combination $combination): void;
}
