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
use Zend\Console\Console;
use ZF\Console\Application;
use ZF\Console\Dispatcher;

chdir(dirname(__DIR__));
require(__DIR__ . '/../vendor/autoload.php');

(function () {
    /* @var ContainerInterface $container */
    $container = require(__DIR__ . '/../config/container.php');
    $config = $container->get('config');
    $dispatcher = new Dispatcher($container);

    $application = new Application(
        $config['name'],
        $config['version'],
        $config['routes'],
        Console::getInstance(),
        $dispatcher
    );

    $application->setDebug($config['debug'])
                ->setBannerDisabledForUserCommands(isset($_ENV['SUBCMD']));

    $exit = $application->run();
    exit ($exit);
})();
