<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Database\Repository\IconFileRepository;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Database\Repository\RepositoryWithOrphansInterface;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the cleanup importer.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CleanupImporterFactory implements FactoryInterface
{
    /**
     * The aliases of the repositories to be cleaned.
     */
    protected const REPOSITORIES = [
        CraftingCategoryRepository::class,
        IconFileRepository::class,
        ItemRepository::class,
        MachineRepository::class,
        RecipeRepository::class,
    ];

    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return CleanupImporter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        return new CleanupImporter(
            $entityManager,
            $this->getRepositories($container)
        );
    }

    /**
     * Returns the repository instances to be cleaned.
     * @param ContainerInterface $container
     * @return array|RepositoryWithOrphansInterface[]
     */
    protected function getRepositories(ContainerInterface $container): array
    {
        $result = [];
        foreach (self::REPOSITORIES as $alias) {
            $result[] = $container->get($alias);
        }
        return $result;
    }
}
