<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Exception;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use Ramsey\Uuid\Uuid;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * The abstract class of commands importing data into a combination.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
abstract class AbstractImportCommand implements CommandInterface
{
    /**
     * The combination repository.
     * @var CombinationRepository
     */
    protected $combinationRepository;

    /**
     * The console.
     * @var Console
     */
    protected $console;

    /**
     * The export data service.
     * @var ExportDataService
     */
    protected $exportDataService;

    /**
     * Initializes the command.
     * @param CombinationRepository $combinationRepository
     * @param Console $console
     * @param ExportDataService $exportDataService
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        Console $console,
        ExportDataService $exportDataService
    ) {
        $this->combinationRepository = $combinationRepository;
        $this->console = $console;
        $this->exportDataService = $exportDataService;
    }

    /**
     * Invokes the command.
     * @param Route $route
     * @param AdapterInterface $consoleAdapter
     * @return int
     */
    public function __invoke(Route $route, AdapterInterface $consoleAdapter): int
    {
        try {
            $this->console->writeStep($this->getLabel());

            $combinationId = Uuid::fromString($route->getMatchedParam('combination'));
            $exportData = $this->exportDataService->loadExport($combinationId->toString());
            $combination = $this->combinationRepository->findById($combinationId);

            if ($combination === null) {
                // @todo Create specific exception.
                throw new ImportException('Combination is missing which should exist.');
            }

            $this->import($exportData, $combination);
            return 0;
        } catch (Exception $e) {
            $this->console->writeException($e);
            return 1;
        }
    }

    /**
     * Returns a label describing what the import is doing.
     * @return string
     */
    abstract protected function getLabel(): string;

    /**
     * Imports the export data into the combination.
     * @param ExportData $exportData
     * @param Combination $combination
     */
    abstract protected function import(ExportData $exportData, Combination $combination): void;
}
