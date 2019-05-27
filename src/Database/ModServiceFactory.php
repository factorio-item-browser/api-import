<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Database;

use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the mod service.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ModServiceFactory implements FactoryInterface
{
    /**
     * Creates the service.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ModService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var ModRepository $modRepository */
        $modRepository = $container->get(ModRepository::class);

        return new ModService($modRepository);
    }
}
