<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\ExportData\Service\ExportDataService;

return [
    'dependencies' => [
        'abstract_factories' => [
            Handler\AbstractCombinationPartHandlerFactory::class,
            Handler\AbstractModPartHandlerFactory::class,
        ],
        'factories'  => [
            Database\CraftingCategoryService::class => Database\CraftingCategoryServiceFactory::class,
            Database\ItemService::class => Database\ItemServiceFactory::class,
            Database\ModService::class => Database\ModServiceFactory::class,

            ExportData\RegistryService::class => ExportData\RegistryServiceFactory::class,

            Handler\ModHandler::class => Handler\ModHandlerFactory::class,

            Importer\Combination\CraftingCategoryImporter::class => Importer\Combination\CraftingCategoryImporterFactory::class,
            Importer\Combination\IconImporter::class => Importer\Combination\IconImporterFactory::class,
            Importer\Combination\ItemImporter::class => Importer\Combination\ItemImporterFactory::class,
            Importer\Combination\MachineImporter::class => Importer\Combination\MachineImporterFactory::class,
            Importer\Combination\RecipeImporter::class => Importer\Combination\RecipeImporterFactory::class,
            Importer\Combination\TranslationImporter::class => Importer\Combination\TranslationImporterFactory::class,
            Importer\Mod\CombinationImporter::class => Importer\Mod\CombinationImporterFactory::class,
            Importer\Mod\DependencyImporter::class => Importer\Mod\DependencyImporterFactory::class,
            Importer\Mod\TranslationImporter::class => Importer\Mod\TranslationImporterFactory::class,

            // 3rd-party services
            ExportDataService::class => ExportData\ExportDataServiceFactory::class,
        ],
    ],
];
