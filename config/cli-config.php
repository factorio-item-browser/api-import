<?php

declare(strict_types=1);

/**
 * The config file used by Doctrine's CLI tools.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Server;

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\HelperSet;

/* @var ContainerInterface $container */
$container = require(__DIR__ . '/../config/container.php');
/* @var EntityManagerInterface $entityManager */
$entityManager = $container->get(EntityManagerInterface::class);

return new HelperSet([
    'em' => new EntityManagerHelper($entityManager),
    'db' => new ConnectionHelper($entityManager->getConnection()),
]);
