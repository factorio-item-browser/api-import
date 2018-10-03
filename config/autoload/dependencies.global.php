<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\ExportData\Service\ExportDataService;

return [
    'dependencies' => [
        'factories'  => [

            // 3rd-party services
            ExportDataService::class => ExportData\ExportDataServiceFactory::class,
        ]
    ],
];
