<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\ExportData\Service\ExportDataService;

return [
    'dependencies' => [
        'factories'  => [
            Importer\CraftingCategoryImporter::class => Importer\CraftingCategoryImporterFactory::class,
            Importer\ItemImporter::class => Importer\ItemImporterFactory::class,
            Importer\MachineImporter::class => Importer\MachineImporterFactory::class,
            Importer\RecipeImporter::class => Importer\RecipeImporterFactory::class,

            // 3rd-party services
            ExportDataService::class => ExportData\ExportDataServiceFactory::class,
        ]
    ],
];
