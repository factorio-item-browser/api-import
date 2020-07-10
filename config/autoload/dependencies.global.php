<?php

declare(strict_types=1);

/**
 * The configuration of the project dependencies.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use BluePsyduck\LaminasAutoWireFactory\AutoWireFactory;
use FactorioItemBrowser\Api\Import\Constant\ConfigKey;

use function BluePsyduck\LaminasAutoWireFactory\injectAliasArray;
use function BluePsyduck\LaminasAutoWireFactory\readConfig;

return [
    'dependencies' => [
        'factories'  => [
            Command\ImportCommand::class => AutoWireFactory::class,
            Command\ImportPartCommand::class => AutoWireFactory::class,
            Command\ProcessCommand::class => AutoWireFactory::class,

            Console\Console::class => AutoWireFactory::class,

            Helper\DataCollector::class => AutoWireFactory::class,
            Helper\IdCalculator::class => AutoWireFactory::class,
            Helper\Validator::class => AutoWireFactory::class,

            Importer\CraftingCategoryImporter::class => AutoWireFactory::class,
            Importer\IconImageImporter::class => AutoWireFactory::class,
            Importer\IconImporter::class => AutoWireFactory::class,
            Importer\ItemImporter::class => AutoWireFactory::class,
            Importer\ItemTranslationImporter::class => AutoWireFactory::class,
            Importer\MachineImporter::class => AutoWireFactory::class,
            Importer\MachineTranslationImporter::class => AutoWireFactory::class,
            Importer\ModImporter::class => AutoWireFactory::class,
            Importer\ModTranslationImporter::class => AutoWireFactory::class,
            Importer\RecipeImporter::class => AutoWireFactory::class,
            Importer\RecipeTranslationImporter::class => AutoWireFactory::class,

            // Auto-wire helpers
            'array $importers' => injectAliasArray(ConfigKey::PROJECT, ConfigKey::API_IMPORT, ConfigKey::IMPORTERS),

            'bool $isDebug' => readConfig('debug'),

            'int $importChunkSize' => readConfig(ConfigKey::PROJECT, ConfigKey::API_IMPORT, ConfigKey::IMPORT_CHUNK_SIZE),
            'int $numberOfParallelProcesses' => readConfig(ConfigKey::PROJECT, ConfigKey::API_IMPORT, ConfigKey::IMPORT_PARALLEL_PROCESSES),
        ],
    ],
];
