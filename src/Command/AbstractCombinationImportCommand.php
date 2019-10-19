<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Constant\ParameterName;
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
abstract class AbstractCombinationImportCommand implements CommandInterface
{
    /**
     * The combination repository.
     * @var CombinationRepository
     */
    protected $combinationRepository;

    /**
     * The export data service.
     * @var ExportDataService
     */
    protected $exportDataService;

    /**
     * Initializes the command.
     * @param CombinationRepository $combinationRepository
     * @param ExportDataService $exportDataService
     */
    public function __construct(CombinationRepository $combinationRepository, ExportDataService $exportDataService)
    {
        $this->combinationRepository = $combinationRepository;
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
        $combinationId = Uuid::fromString($route->getMatchedParam(ParameterName::COMBINATION));
        $exportData = $this->exportDataService->loadExport($combinationId->toString());
        $combination = $this->combinationRepository->findById($combinationId);

        $this->import($exportData, $combination);

        return 0;
    }

    /**
     * Imports the export data into the combination.
     * @param ExportData $exportData
     * @param Combination $combination
     */
    abstract protected function import(ExportData $exportData, Combination $combination): void;
}
