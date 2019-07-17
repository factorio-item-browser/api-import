<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Handler;

use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Constant\ServiceName;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Mod\CombinationImporter;
use FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporter;
use FactorioItemBrowser\Api\Import\Importer\Mod\ThumbnailImporter;
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
        ServiceName::MOD_COMBINATIONS_HANDLER => CombinationImporter::class,
        ServiceName::MOD_DEPENDENCIES_HANDLER => DependencyImporter::class,
        ServiceName::MOD_THUMBNAIL_HANDLER => ThumbnailImporter::class,
        ServiceName::MOD_TRANSLATIONS_HANDLER => TranslationImporter::class,
    ];

    /**
     * Can the factory create an instance for the service?
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
        /* @var ModRepository $modRepository */
        $modRepository = $container->get(ModRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $container->get(RegistryService::class);

        return new ModPartHandler(
            $container->get(self::IMPORTER_MAP[$requestedName]),
            $modRepository,
            $registryService
        );
    }
}
