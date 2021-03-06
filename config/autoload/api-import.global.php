<?php

/**
 * The configuration of the API import itself.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\Api\Import\Constant\ConfigKey;
use FactorioItemBrowser\Api\Import\Constant\ImporterName;

return [
    ConfigKey::MAIN => [
        ConfigKey::IMPORT_CHUNK_SIZE => 2048,
        ConfigKey::IMPORT_PARALLEL_PROCESSES => 8,
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
];
