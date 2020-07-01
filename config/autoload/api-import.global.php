<?php

declare(strict_types=1);

/**
 * The configuration of the API import itself.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\Api\Import\Constant\ConfigKey;
use FactorioItemBrowser\Api\Import\Constant\ImporterName;

return [
    ConfigKey::PROJECT => [
        ConfigKey::API_IMPORT => [
            ConfigKey::IMPORT_CHUNK_SIZE => 128,
            ConfigKey::IMPORTERS => [
                Importer\CraftingCategoryImporter::class,
                Importer\IconImageImporter::class,
                Importer\IconImporter::class,
                Importer\ItemImporter::class,
                Importer\MachineImporter::class,
                Importer\ModImporter::class,
                Importer\RecipeImporter::class,
            ],
            'new-importers' => [
                ImporterName::CRAFTING_CATEGORY => NewImporter\CraftingCategoryImporter::class,
                ImporterName::ITEM => NewImporter\ItemImporter::class,
                ImporterName::MOD => NewImporter\ModImporter::class,

                ImporterName::MACHINE => NewImporter\MachineImporter::class,
                ImporterName::RECIPE => NewImporter\RecipeImporter::class,
            ],
        ],
    ],
];
