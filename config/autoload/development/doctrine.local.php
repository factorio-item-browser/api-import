<?php

declare(strict_types=1);

/**
 * The configuration file for doctrine.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use Doctrine\DBAL\Driver\PDO\MySql\Driver as PDOMySqlDriver;
use PDO;

return [
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'driverClass' => PDOMySqlDriver::class,
                'params' => [
                    'host'     => 'fib-ai-mysql', // Change to 'fib-as-mysql' to share with the local API server.
                    'port'     => '3306',
                    'user'     => 'api',
                    'password' => 'api',
                    'dbname'   => 'api',
                    'driverOptions' => [
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                    ],
                ],
            ],
        ],
    ],
];
