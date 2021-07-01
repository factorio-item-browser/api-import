<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command for cleaning up the database tables.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CleanCommand extends Command
{
    private Console $console;

    /**
     * @var array<string, ImporterInterface>
     */
    private array $importers;

    /**
     *
     * @param Console $console
     * @param array<string, ImporterInterface> $importers
     */
    public function __construct(Console $console, array $importers)
    {
        parent::__construct();

        $this->console = $console;
        $this->importers = $importers;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName(CommandName::CLEAN);
        $this->setDescription('Cleans the database tables from unneeded data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (array_reverse($this->importers) as $name => $importer) {
            $this->console->writeAction("Importer: {$name}");
            $importer->cleanup();
        }

        return self::SUCCESS;
    }
}
