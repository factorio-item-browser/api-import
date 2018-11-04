<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\Api\Import\Handler\AbstractCombinationPartHandlerFactory;
use FactorioItemBrowser\ExportData\Service\ExportDataService;

return [
    'dependencies' => [
        'abstract_factories' => [
            AbstractCombinationPartHandlerFactory::class,
        ],
        'factories'  => [
            ExportData\RegistryService::class => ExportData\RegistryServiceFactory::class,

            Importer\CombinationPart\CraftingCategoryImporter::class => Importer\CombinationPart\CraftingCategoryImporterFactory::class,
            Importer\CombinationPart\IconImporter::class => Importer\CombinationPart\IconImporterFactory::class,
            Importer\CombinationPart\ItemImporter::class => Importer\CombinationPart\ItemImporterFactory::class,
            Importer\CombinationPart\MachineImporter::class => Importer\CombinationPart\MachineImporterFactory::class,
            Importer\CombinationPart\RecipeImporter::class => Importer\CombinationPart\RecipeImporterFactory::class,
            Importer\CombinationPart\TranslationImporter::class => Importer\CombinationPart\TranslationImporterFactory::class,

            Database\CraftingCategoryService::class => Database\CraftingCategoryServiceFactory::class,
            Database\ItemService::class => Database\ItemServiceFactory::class,

            // 3rd-party services
            ExportDataService::class => ExportData\ExportDataServiceFactory::class,
        ],
    ],
];
