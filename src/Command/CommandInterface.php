<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * The interface defining the signature of the command invoke method.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface CommandInterface
{
    /**
     * Invokes the command.
     * @param Route $route
     * @param AdapterInterface $consoleAdapter
     * @return int
     */
    public function __invoke(Route $route, AdapterInterface $consoleAdapter): int;
}
