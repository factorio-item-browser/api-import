<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Import\Database\CraftingCategoryService;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Database\ItemService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the recipe importer.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class RecipeImporterFactory implements FactoryInterface
{
    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return RecipeImporter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $container->get(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        /* @var ItemService $itemService */
        $itemService = $container->get(ItemService::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $container->get(RecipeRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $container->get(RegistryService::class);

        return new RecipeImporter(
            $craftingCategoryService,
            $entityManager,
            $itemService,
            $recipeRepository,
            $registryService
        );
    }
}
