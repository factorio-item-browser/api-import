<?php

namespace FactorioItemBrowser\Api\Import\Constant;

/**
 * The interface holding the names of the virtual services.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface ServiceName
{
    public const COMBINATION_CRAFTING_CATEGORIES_HANDLER = 'handler.combination.craftingCategories';
    public const COMBINATION_ICONS_HANDLER = 'handler.combination.icons';
    public const COMBINATION_ITEMS_HANDLER = 'handler.combination.items';
    public const COMBINATION_MACHINES_HANDLER = 'handler.combination.machines';
    public const COMBINATION_RECIPES_HANDLER = 'handler.combination.recipes';
    public const COMBINATION_TRANSLATIONS_HANDLER = 'handler.combination.translations';

    public const MOD_COMBINATIONS_HANDLER = 'handle.mod.combinations';
    public const MOD_DEPENDENCIES_HANDLER = 'handler.mod.dependencies';
    public const MOD_TRANSLATIONS_HANDLER = 'handler.mod.translations';
}
