#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The main CLI script of the export.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

chdir(dirname(__DIR__));
require(__DIR__ . '/../vendor/autoload.php');

(function () {
    /* @var ContainerInterface $container */
    $container = require(__DIR__ . '/../config/container.php');
    $config = $container->get('config');

    $application = new Application($config['name'], $config['version']);
    $application->setCommandLoader(new ContainerCommandLoader($container, $config['commands']));

    $exit = $application->run();
    exit ($exit);
})();
