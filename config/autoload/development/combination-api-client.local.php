<?php

/**
 * The configuration of the export scripts.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

declare(strict_types=1);

namespace FactorioItemBrowser\Export;

use FactorioItemBrowser\CombinationApi\Client\Constant\ConfigKey;

return [
    ConfigKey::MAIN => [
        ConfigKey::BASE_URI => 'http://combination-api.fib.dev',
        ConfigKey::API_KEY => 'factorio-item-browser',
        ConfigKey::TIMEOUT => 60,
    ],
];
