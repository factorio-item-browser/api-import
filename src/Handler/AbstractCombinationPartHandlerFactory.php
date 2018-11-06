<?php

namespace FactorioItemBrowser\Api\Import\Handler;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Repository\ModCombinationRepository;
use FactorioItemBrowser\Api\Import\Constant\ServiceName;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\CraftingCategoryImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\IconImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\ItemImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\MachineImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\RecipeImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\TranslationImporter;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * The abstract factory of the combination part handler.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class AbstractCombinationPartHandlerFactory implements AbstractFactoryInterface
{
    /**
     * The map of the service name to the importer.
     */
    protected const IMPORTER_MAP = [
        ServiceName::COMBINATION_CRAFTING_CATEGORIES_HANDLER => CraftingCategoryImporter::class,
        ServiceName::COMBINATION_ICONS_HANDLER => IconImporter::class,
        ServiceName::COMBINATION_ITEMS_HANDLER => ItemImporter::class,
        ServiceName::COMBINATION_MACHINES_HANDLER => MachineImporter::class,
        ServiceName::COMBINATION_RECIPES_HANDLER => RecipeImporter::class,
        ServiceName::COMBINATION_TRANSLATIONS_HANDLER => TranslationImporter::class,
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
     * @return CombinationPartHandler
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $container->get(RegistryService::class);

        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $entityManager->getRepository(ModCombination::class);

        return new CombinationPartHandler(
            $container->get(self::IMPORTER_MAP[$requestedName]),
            $modCombinationRepository,
            $registryService
        );
    }
}
