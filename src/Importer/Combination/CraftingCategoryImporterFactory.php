<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the crafting category importer.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CraftingCategoryImporterFactory implements FactoryInterface
{
    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return CraftingCategoryImporter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $container->get(CraftingCategoryRepository::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        /* @var RegistryService $registryService */
        $registryService = $container->get(RegistryService::class);

        return new CraftingCategoryImporter(
            $craftingCategoryRepository,
            $entityManager,
            $registryService
        );
    }
}
