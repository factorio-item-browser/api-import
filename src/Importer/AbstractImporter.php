<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use FactorioItemBrowser\ExportData\ExportData;
use Generator;
use LimitIterator;

/**
 * The abstract class of all importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @template TExport
 */
abstract class AbstractImporter implements ImporterInterface
{
    /**
     * Counts the entities the importer has to process.
     * @param ExportData $exportData
     * @return int
     */
    public function count(ExportData $exportData): int
    {
        return count(iterator_to_array($this->getExportEntities($exportData)));
    }

    /**
     * Returns a chunk from the export entities.
     * @param ExportData $exportData
     * @param int $offset
     * @param int $limit
     * @return array<TExport>
     */
    protected function getChunkedExportEntities(ExportData $exportData, int $offset, int $limit): array
    {
        $iterator = new LimitIterator($this->getExportEntities($exportData), $offset, $limit);
        return iterator_to_array($iterator);
    }

    /**
     * Returns all the entities from the export data as generator.
     * @param ExportData $exportData
     * @return Generator<int, TExport, null, null>
     */
    abstract protected function getExportEntities(ExportData $exportData): Generator;
}
