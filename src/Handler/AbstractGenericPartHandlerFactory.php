<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Handler;

use FactorioItemBrowser\Api\Import\Constant\ServiceName;
use FactorioItemBrowser\Api\Import\Importer\Generic\CleanupImporter;
use FactorioItemBrowser\Api\Import\Importer\Generic\ClearCacheImporter;
use FactorioItemBrowser\Api\Import\Importer\Generic\CombinationOrderImporter;
use FactorioItemBrowser\Api\Import\Importer\Generic\ModOrderImporter;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * The abstract factory of the generic part handler.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class AbstractGenericPartHandlerFactory implements AbstractFactoryInterface
{
    /**
     * The map of the service name to the importer.
     */
    protected const IMPORTER_MAP = [
        ServiceName::GENERIC_CLEANUP => CleanupImporter::class,
        ServiceName::GENERIC_CLEAR_CACHE => ClearCacheImporter::class,
        ServiceName::GENERIC_ORDER_COMBINATIONS => CombinationOrderImporter::class,
        ServiceName::GENERIC_ORDER_MODS => ModOrderImporter::class,
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
     * @return GenericPartHandler
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new GenericPartHandler(
            $container->get(self::IMPORTER_MAP[$requestedName])
        );
    }
}
