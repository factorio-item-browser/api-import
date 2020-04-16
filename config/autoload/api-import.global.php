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

return [
    ConfigKey::PROJECT => [
        ConfigKey::API_IMPORT => [
            ConfigKey::IMPORTERS => [
                Importer\CraftingCategoryImporter::class,
                Importer\IconImageImporter::class,
                Importer\IconImporter::class,
                Importer\ItemImporter::class,
                Importer\MachineImporter::class,
                Importer\ModImporter::class,
                Importer\RecipeImporter::class,
            ],
        ],
    ],
];
