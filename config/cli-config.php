<?php

declare(strict_types=1);

/**
 * The config file used by Doctrine's CLI tools.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

/* @var ContainerInterface $container */
$container = require(__DIR__  . '/container.php');
/* @var EntityManagerInterface $entityManager */
$entityManager = $container->get(EntityManagerInterface::class);

return new HelperSet([
    'em' => new EntityManagerHelper($entityManager),
    'question' => new QuestionHelper(),
    'configuration' => new ConfigurationHelper(
        $entityManager->getConnection(),
        $container->get('doctrine.migrations.orm_default')
    ),
]);
