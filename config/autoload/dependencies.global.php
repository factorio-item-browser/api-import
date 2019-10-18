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
use function BluePsyduck\ZendAutoWireFactory\injectAliasArray;

return [
    'dependencies' => [
        'abstract_factories' => [
            Handler\AbstractCombinationPartHandlerFactory::class,
            Handler\AbstractGenericPartHandlerFactory::class,
            Handler\AbstractModPartHandlerFactory::class,
        ],
        'factories'  => [
            Command\ProcessCommand::class => AutoWireFactory::class,

            Helper\IdCalculator::class => AutoWireFactory::class,

            Importer\CraftingCategoryImporter::class => AutoWireFactory::class,
            Importer\ItemImporter::class => AutoWireFactory::class,
            Importer\MachineImporter::class => AutoWireFactory::class,
            Importer\ModImporter::class => AutoWireFactory::class,
            Importer\RecipeImporter::class => AutoWireFactory::class,
            Importer\TranslationImporter::class => AutoWireFactory::class,

            // Auto-wire helpers
            'array $importers' => injectAliasArray(ConfigKey::PROJECT, ConfigKey::API_IMPORT, ConfigKey::IMPORTERS),
        ],
    ],
];
