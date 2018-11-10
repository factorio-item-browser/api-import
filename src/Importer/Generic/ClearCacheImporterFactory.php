<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\CachedSearchResult;
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
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        /* @var CachedSearchResultRepository $cachedSearchResultRepository */
        $cachedSearchResultRepository = $entityManager->getRepository(CachedSearchResult::class);

        return new ClearCacheImporter($cachedSearchResultRepository);
    }
}
