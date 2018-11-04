<?php

namespace FactorioItemBrowser\Api\Import\Handler;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 *
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ModHandlerFactory implements FactoryInterface
{
    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ModHandler
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $container->get(RegistryService::class);


        return new ModHandler(
            $registryService
        );
    }
}
