<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
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
     * @var ImporterInterface[]
     */
    protected $importers;

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
        $exportData = $this->exportDataService->loadExport('foo');

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
           ->from(Combination::class, 'c');

        $combination = $qb->getQuery()->getResult()[0];

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

        foreach ($this->importers as $importer) {
            $importer->cleanup();
        }
        $this->entityManager->flush();
        die;
    }
}
