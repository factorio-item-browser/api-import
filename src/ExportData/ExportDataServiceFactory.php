<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\ExportData;

use FactorioItemBrowser\ExportData\Registry\Adapter\FileSystemAdapter;
use FactorioItemBrowser\ExportData\Service\ExportDataService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the export data service.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ExportDataServiceFactory implements FactoryInterface
{
    /**
     * Creates the raw export data service.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ExportDataService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $adapter = new FileSystemAdapter($config['factorio-item-browser']['export-data']['directory']);

        return new ExportDataService($adapter);
    }
}
