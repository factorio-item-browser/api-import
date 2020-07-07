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
            ConfigKey::IMPORT_CHUNK_SIZE => 256,
            ConfigKey::IMPORTERS => [
                ImporterName::CRAFTING_CATEGORY => Importer\CraftingCategoryImporter::class,
                ImporterName::ITEM => Importer\ItemImporter::class,
                ImporterName::MOD => Importer\ModImporter::class,

                ImporterName::MACHINE => Importer\MachineImporter::class,
                ImporterName::RECIPE => Importer\RecipeImporter::class,

                ImporterName::MOD_TRANSLATION => Importer\ModTranslationImporter::class,
                ImporterName::ITEM_TRANSLATION => Importer\ItemTranslationImporter::class,
                ImporterName::MACHINE_TRANSLATION => Importer\MachineTranslationImporter::class,
                ImporterName::RECIPE_TRANSLATION => Importer\RecipeTranslationImporter::class,

                ImporterName::ICON_IMAGE => Importer\IconImageImporter::class,
                ImporterName::ICON => Importer\IconImporter::class,
            ],
        ],
    ],
];
