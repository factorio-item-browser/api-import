<?php

declare(strict_types=1);

/**
 * The configuration of the Factorio Item Browser itself.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\Api\Database;
use FactorioItemBrowser\Api\Import\Constant\ConfigKey;

return [
    ConfigKey::PROJECT => [
        ConfigKey::API_IMPORT => [
            ConfigKey::REPOSITORIES_WITH_ORPHANS => [
                Database\Repository\CraftingCategoryRepository::class,
                Database\Repository\IconFileRepository::class,
                Database\Repository\ItemRepository::class,
                Database\Repository\MachineRepository::class,
                Database\Repository\RecipeRepository::class,
            ],
        ],
    ],
];
