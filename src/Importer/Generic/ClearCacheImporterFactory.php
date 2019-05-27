<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use FactorioItemBrowser\Api\Database\Repository\CachedSearchResultRepository;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 *
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ClearCacheImporterFactory implements FactoryInterface
{
    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ClearCacheImporter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var CachedSearchResultRepository $cachedSearchResultRepository */
        $cachedSearchResultRepository = $container->get(CachedSearchResultRepository::class);

        return new ClearCacheImporter($cachedSearchResultRepository);
    }
}
