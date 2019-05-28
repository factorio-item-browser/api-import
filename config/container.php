<?php

declare(strict_types=1);

/**
 * The file providing the container.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use BluePsyduck\ZendAutoWireFactory\AutoWireFactory;
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

// Load configuration
$config = require __DIR__ . '/config.php';

// Build container
$container = new ServiceManager();
(new Config($config['dependencies']))->configureServiceManager($container);

if ($config[ConfigAggregator::ENABLE_CACHE]) {
    AutoWireFactory::setCacheFile(__DIR__ . '../data/cache/autowire-factory-cache.php');
}

// Inject config
$container->setService('config', $config);

return $container;
