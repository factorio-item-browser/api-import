<?php

namespace FactorioItemBrowser\Api\Import\Handler;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Constant\ServiceName;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporter;
use FactorioItemBrowser\Api\Import\Importer\Mod\TranslationImporter;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * The abstract factory of the mod part handler.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class AbstractModPartHandlerFactory implements AbstractFactoryInterface
{
    /**
     * The map of the service name to the importer.
     */
    protected const IMPORTER_MAP = [
        ServiceName::MOD_DEPENDENCIES_HANDLER => DependencyImporter::class,
        ServiceName::MOD_TRANSLATIONS_HANDLER => TranslationImporter::class,
    ];

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return isset(self::IMPORTER_MAP[$requestedName]);
    }

    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ModPartHandler
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $container->get(RegistryService::class);

        /* @var ModRepository $modRepository */
        $modRepository = $entityManager->getRepository(Mod::class);

        return new ModPartHandler(
            $container->get(self::IMPORTER_MAP[$requestedName]),
            $modRepository,
            $registryService
        );
    }
}
