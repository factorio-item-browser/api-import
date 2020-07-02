<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Constant;

/**
 * The interface holding the names of the importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface ImporterName
{
    public const CRAFTING_CATEGORY = 'crafting-category';
    public const ITEM = 'item';
    public const ITEM_TRANSLATION = 'item-translation';
    public const MACHINE = 'machine';
    public const MACHINE_TRANSLATION = 'machine-translation';
    public const MOD = 'mod';
    public const MOD_TRANSLATION = 'mod-translation';
    public const RECIPE = 'recipe';
    public const RECIPE_TRANSLATION = 'recipe-translation';
}
