<?php

namespace FactorioItemBrowser\Api\Import\Importer\Mod;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Import\Database\ModService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the dependency importer.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class DependencyImporterFactory implements FactoryInterface
{
    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return DependencyImporter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /* @var ModService $modService */
        $modService = $container->get(ModService::class);

        return new DependencyImporter(
            $entityManager,
            $modService
        );
    }
}
