<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Export;

use FactorioItemBrowser\ExportQueue\Client\Constant\ConfigKey;

/**
 * The configuration of the export scripts.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
return [
    ConfigKey::PROJECT => [
        ConfigKey::EXPORT_QUEUE_CLIENT => [
            ConfigKey::OPTIONS => [
                ConfigKey::OPTION_API_URL => 'http://fib-eq-php',
                ConfigKey::OPTION_API_KEY => 'factorio-item-browser',
            ],
        ],
    ],
];
