<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The interface of the importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface ImporterInterface
{
    /**
     * Counts the entities the importer has to process.
     * @param ExportData $exportData
     * @return int
     */
    public function count(ExportData $exportData): int;

    /**
     * Prepares the combination for the import.
     * @param Combination $combination
     */
    public function prepare(Combination $combination): void;

    /**
     * Imports the specified chunk of the data from the export data.
     * @param Combination $combination
     * @param ExportData $exportData
     * @param int $offset
     * @param int $limit
     */
    public function import(Combination $combination, ExportData $exportData, int $offset, int $limit): void;

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void;
}
