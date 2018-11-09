<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Mod;

use FactorioItemBrowser\Api\Database\Entity\Mod  as DatabaseMod;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;

/**
 * The interface of the mod importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface ModImporterInterface
{
    /**
     * Imports the specified export mod into the database one.
     * @param ExportMod $exportMod
     * @param DatabaseMod $databaseMod
     * @throws ImportException
     */
    public function import(ExportMod $exportMod, DatabaseMod $databaseMod): void;
}
