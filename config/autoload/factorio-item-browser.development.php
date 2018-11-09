<?php

declare(strict_types=1);

/**
 * The configuration of the Factorio Item Browser itself.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

return [
    'factorio-item-browser' => [
        'api-import' => [
            'api-keys' => [
                'debug' => 'factorio-item-browser',
            ],
        ],
        'export-data' => [
            'directory' => __DIR__ . '/../../data/export',
        ],
    ],
];
