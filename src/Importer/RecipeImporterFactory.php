<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Recipe;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Import\Service\CraftingCategoryService;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Service\ItemService;
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
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /* @var ItemService $itemService */
        $itemService = $container->get(ItemService::class);
        /* @var RegistryService $registryService */
        $registryService = $container->get(RegistryService::class);

        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $entityManager->getRepository(Recipe::class);

        return new RecipeImporter(
            $craftingCategoryService,
            $entityManager,
            $itemService,
            $recipeRepository,
            $registryService
        );
    }
}
