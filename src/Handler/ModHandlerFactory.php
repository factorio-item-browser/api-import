<?php

namespace FactorioItemBrowser\Api\Import\Handler;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the mod handler.
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

        /* @var ModRepository $modRepository */
        $modRepository = $entityManager->getRepository(Mod::class);

        return new ModHandler(
            $entityManager,
            $modRepository,
            $registryService
        );
    }
}
