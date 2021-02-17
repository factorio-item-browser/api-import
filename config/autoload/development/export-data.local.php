<?php

/**
 * The configuration of the export-data library.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\ExportData\Constant\ConfigKey;

return [
    ConfigKey::MAIN => [
        ConfigKey::WORKING_DIRECTORY => __DIR__ . '/../../../data/temp',
    ],
];
