<?php

declare(strict_types=1);

/**
 * The configuration of the project dependencies.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use BluePsyduck\ZendAutoWireFactory\AutoWireFactory;
use FactorioItemBrowser\Api\Import\Constant\ConfigKey;
use FactorioItemBrowser\ExportData\Service\ExportDataService;
use Zend\Expressive\Middleware\ErrorResponseGenerator;
use function BluePsyduck\ZendAutoWireFactory\injectAliasArray;
use function BluePsyduck\ZendAutoWireFactory\readConfig;

return [
    'dependencies' => [
        'abstract_factories' => [
            Handler\AbstractCombinationPartHandlerFactory::class,
            Handler\AbstractGenericPartHandlerFactory::class,
            Handler\AbstractModPartHandlerFactory::class,
        ],
        'factories'  => [
            Database\CraftingCategoryService::class => AutoWireFactory::class,
            Database\ItemService::class => AutoWireFactory::class,
            Database\ModService::class => AutoWireFactory::class,

            ExportData\RegistryService::class => AutoWireFactory::class,

            Handler\ModHandler::class => AutoWireFactory::class,

            Importer\Combination\CraftingCategoryImporter::class => AutoWireFactory::class,
            Importer\Combination\IconImporter::class => AutoWireFactory::class,
            Importer\Combination\ItemImporter::class => AutoWireFactory::class,
            Importer\Combination\MachineImporter::class => AutoWireFactory::class,
            Importer\Combination\RecipeImporter::class => AutoWireFactory::class,
            Importer\Combination\TranslationImporter::class => AutoWireFactory::class,
            Importer\Generic\CleanupImporter::class => AutoWireFactory::class,
            Importer\Generic\ClearCacheImporter::class => AutoWireFactory::class,
            Importer\Generic\CombinationOrderImporter::class => AutoWireFactory::class,
            Importer\Generic\ModOrderImporter::class => AutoWireFactory::class,
            Importer\Mod\CombinationImporter::class => AutoWireFactory::class,
            Importer\Mod\DependencyImporter::class => AutoWireFactory::class,
            Importer\Mod\ThumbnailImporter::class => AutoWireFactory::class,
            Importer\Mod\TranslationImporter::class => AutoWireFactory::class,

            Middleware\ApiKeyMiddleware::class => AutoWireFactory::class,

            // Auto-wire helpers
            'array $apiKeys' => readConfig(ConfigKey::PROJECT, ConfigKey::API_IMPORT, ConfigKey::API_KEYS),
            'array $repositoriesWithOrphans' => injectAliasArray(ConfigKey::PROJECT, ConfigKey::API_IMPORT, ConfigKey::REPOSITORIES_WITH_ORPHANS),

            // 3rd-party services
            ErrorResponseGenerator::class => Response\ErrorResponseGeneratorFactory::class,
            ExportDataService::class => ExportData\ExportDataServiceFactory::class,
        ],
    ],
];
