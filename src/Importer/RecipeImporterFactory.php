<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Entity\Recipe;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
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
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $container->get(RegistryService::class);

        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $entityManager->getRepository(CraftingCategory::class);
        /* @var ItemRepository $itemRepository */
        $itemRepository = $entityManager->getRepository(Item::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $entityManager->getRepository(Recipe::class);

        return new RecipeImporter(
            $craftingCategoryRepository,
            $entityManager,
            $itemRepository,
            $recipeRepository,
            $registryService
        );
    }
}
