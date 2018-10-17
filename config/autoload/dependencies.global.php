<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\ExportData\Service\ExportDataService;

return [
    'dependencies' => [
        'factories'  => [
            ExportData\RegistryService::class => ExportData\RegistryServiceFactory::class,

            Importer\CraftingCategoryImporter::class => Importer\CraftingCategoryImporterFactory::class,
            Importer\IconImporter::class => Importer\IconImporterFactory::class,
            Importer\ItemImporter::class => Importer\ItemImporterFactory::class,
            Importer\MachineImporter::class => Importer\MachineImporterFactory::class,
            Importer\RecipeImporter::class => Importer\RecipeImporterFactory::class,
            Importer\TranslationImporter::class => Importer\TranslationImporterFactory::class,

            Service\CraftingCategoryService::class => Service\CraftingCategoryServiceFactory::class,
            Service\ItemService::class => Service\ItemServiceFactory::class,

            // 3rd-party services
            ExportDataService::class => ExportData\ExportDataServiceFactory::class,
        ]
    ],
];
