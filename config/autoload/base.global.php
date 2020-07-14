<?php

declare(strict_types=1);

/**
 * The base configuration file.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use Laminas\ConfigAggregator\ConfigAggregator;

return [
    ConfigAggregator::ENABLE_CACHE => true,
    'debug' => false,
    'name' => 'Factorio Item Browser - Api Import',
    'version' => '1.1.0',
];
