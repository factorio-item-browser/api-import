<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use FactorioItemBrowser\Api\Import\Exception\ImportException;

/**
 * The interface for the generic importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface GenericImporterInterface
{
    /**
     * Imports some generic data.
     * @throws ImportException
     */
    public function import(): void;
}
