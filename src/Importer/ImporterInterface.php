<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowserTest\Api\Import\Exception\ImportException;

/**
 * The interface of the importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface ImporterInterface
{
    /**
     * Imports the items.
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @throws ImportException
     */
    public function import(ExportCombination $exportCombination, DatabaseCombination $databaseCombination): void;
}
