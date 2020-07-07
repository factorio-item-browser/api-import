<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Exception;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * The command for importing a part of the data.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ImportPartCommand extends AbstractImportCommand
{
    /**
     * @var array<ImporterInterface>
     */
    protected array $importers;

    protected ImporterInterface $importer;
    protected int $offset;
    protected int $limit;

    /**
     * @param CombinationRepository $combinationRepository
     * @param Console $console
     * @param ExportDataService $exportDataService
     * @param array<ImporterInterface> $importers
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        Console $console,
        ExportDataService $exportDataService,
        array $importers
    ) {
        parent::__construct($combinationRepository, $console, $exportDataService);
        $this->importers = $importers;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName(CommandName::IMPORT_PART);
        $this->setDescription('Imports a part of the data of a combination');

        $this->addArgument('part', InputArgument::REQUIRED, 'The part to import.');
        $this->addArgument('offset', InputArgument::REQUIRED, 'The offset to start the import at.');
        $this->addArgument('limit', InputArgument::REQUIRED, 'The limit of entities to import.');
    }

    /**
     * @param InputInterface $input
     * @throws Exception
     */
    protected function processInput(InputInterface $input): void
    {
        $part = strval($input->getArgument('part'));
        if (!isset($this->importers[$part])) {
            throw new Exception('Unknown part: ' . $part);
        }

        $this->importer = $this->importers[$part];
        $this->offset = intval($input->getArgument('offset'));
        $this->limit = intval($input->getArgument('limit'));
    }

    protected function import(ExportData $exportData, Combination $combination): void
    {
        $this->importer->import($combination, $exportData, $this->offset, $this->limit);
    }
}
