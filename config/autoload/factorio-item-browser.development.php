<?php

declare(strict_types=1);

/**
 * The configuration of the Factorio Item Browser itself.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\Api\Import\Constant\ConfigKey;

return [
    ConfigKey::PROJECT => [
        ConfigKey::API_IMPORT => [
            ConfigKey::API_KEYS => [
                'debug' => 'factorio-item-browser',
            ],
        ],
        ConfigKey::EXPORT_DATA => [
            ConfigKey::EXPORT_DATA_DIRECTORY => __DIR__ . '/../../data/export',
        ]
    ],
];
