<?php

namespace FactorioItemBrowser\Api\Import\Constant;

/**
 * The interface holding the route names as constants.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface RouteName
{
    public const CLEANUP = 'cleanup';

    public const COMBINATION_CRAFTING_CATEGORIES = 'combination.crafting-categories';
    public const COMBINATION_ICONS = 'combination.icons';
    public const COMBINATION_ITEMS = 'combination.items';
    public const COMBINATION_MACHINES = 'combination.machines';
    public const COMBINATION_RECIPES = 'combination.recipes';
    public const COMBINATION_TRANSLATIONS = 'combination.translations';

    public const MOD = 'mod';
    public const MOD_COMBINATIONS = 'mod.combinations';
    public const MOD_DEPENDENCIES = 'mod.dependencies';
    public const MOD_TRANSLATIONS = 'mod.translations';

    public const ORDER_COMBINATIONS = 'order.combinations';
    public const ORDER_MODS = 'order.mods';
}
