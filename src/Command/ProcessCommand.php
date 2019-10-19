<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use FactorioItemBrowser\ExportData\ExportDataService;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * The command for processing the next job in the import queue.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ProcessCommand implements CommandInterface
{
    /**
     * The combination repository.
     * @var CombinationRepository
     */
    protected $combinationRepository;

    /**
     * The entity manager.
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * The export data service.
     * @var ExportDataService
     */
    protected $exportDataService;

    /**
     * Initializes the command.
     * @param ExportDataService $exportDataService
     */
    public function __construct(EntityManagerInterface $entityManager, ExportDataService $exportDataService, array $importers)
    {
        $this->entityManager = $entityManager;
        $this->exportDataService = $exportDataService;
        $this->importers = $importers;
    }

    /**
     * Invokes the command.
     * @param Route $route
     * @param AdapterInterface $consoleAdapter
     * @return int
     */
    public function __invoke(Route $route, AdapterInterface $consoleAdapter): int
    {
        $exportData = $this->exportDataService->loadExport('6e6f47e8-5727-44c0-b759-ef9ed5f994a2');


    }
}
