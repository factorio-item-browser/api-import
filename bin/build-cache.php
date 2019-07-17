#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The script for building up the config caches.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Server;

use Psr\Container\ContainerInterface;

chdir(dirname(__DIR__));
require(__DIR__ . '/../vendor/autoload.php');

(function () {
    /* @var ContainerInterface $container */
    $container = require(__DIR__ . '/../config/container.php');
    $config = $container->get('config');

    foreach (array_keys($config['dependencies']['factories'] ?? []) as $alias) {
        $container->get($alias);
    }
})();
