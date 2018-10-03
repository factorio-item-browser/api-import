<?php

declare(strict_types=1);

ini_set('serialize_precision', '-1');

chdir(dirname(__DIR__));
require(__DIR__ . '/../vendor/autoload.php');

(function () {
    /* @var \Psr\Container\ContainerInterface $container */
    $container = require(__DIR__ . '/../config/container.php');

    /* @var \Zend\Expressive\Application $app */
    $app = $container->get(\Zend\Expressive\Application::class);
    $factory = $container->get(\Zend\Expressive\MiddlewareFactory::class);

    (require(__DIR__ . '/../config/pipeline.php'))($app, $factory, $container);
    (require(__DIR__ . '/../config/routes.php'))($app, $factory, $container);

    $app->run();
})();
