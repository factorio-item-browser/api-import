<?php

declare(strict_types=1);

/**
 * The configuration of the export-data library.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\ExportData\Constant\ConfigKey;

return [
    ConfigKey::PROJECT => [
        ConfigKey::EXPORT_DATA => [
//            ConfigKey::CACHE_DIR => __DIR__ . '/../../data/cache/export-data',
            ConfigKey::WORKING_DIRECTORY => __DIR__ . '/../../../data/temp',
        ],
    ],
];
