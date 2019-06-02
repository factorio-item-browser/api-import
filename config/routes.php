<?php

declare(strict_types=1);

/**
 * The file providing the routes.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\Api\Import\Constant\RouteName;
use FactorioItemBrowser\Api\Import\Constant\ServiceName;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/combination/{combinationHash}/crafting-categories', ServiceName::COMBINATION_CRAFTING_CATEGORIES_HANDLER, RouteName::COMBINATION_CRAFTING_CATEGORIES);
    $app->get('/combination/{combinationHash}/icons', ServiceName::COMBINATION_ICONS_HANDLER, RouteName::COMBINATION_ICONS);
    $app->get('/combination/{combinationHash}/items', ServiceName::COMBINATION_ITEMS_HANDLER, RouteName::COMBINATION_ITEMS);
    $app->get('/combination/{combinationHash}/machines', ServiceName::COMBINATION_MACHINES_HANDLER, RouteName::COMBINATION_MACHINES);
    $app->get('/combination/{combinationHash}/recipes', ServiceName::COMBINATION_RECIPES_HANDLER, RouteName::COMBINATION_RECIPES);
    $app->get('/combination/{combinationHash}/translations', ServiceName::COMBINATION_TRANSLATIONS_HANDLER, RouteName::COMBINATION_TRANSLATIONS);

    $app->get('/cleanup', ServiceName::GENERIC_CLEANUP, RouteName::CLEANUP);
    $app->get('/clear-cache', ServiceName::GENERIC_CLEAR_CACHE, RouteName::CLEAR_CACHE);

    $app->get('/mod/{modName}', Handler\ModHandler::class, RouteName::MOD);
    $app->get('/mod/{modName}/combinations', ServiceName::MOD_COMBINATIONS_HANDLER, RouteName::MOD_COMBINATIONS);
    $app->get('/mod/{modName}/dependencies', ServiceName::MOD_DEPENDENCIES_HANDLER, RouteName::MOD_DEPENDENCIES);
    $app->get('/mod/{modName}/thumbnail', ServiceName::MOD_THUMBNAIL_HANDLER, RouteName::MOD_THUMBNAIL);
    $app->get('/mod/{modName}/translations', ServiceName::MOD_TRANSLATIONS_HANDLER, RouteName::MOD_TRANSLATIONS);

    $app->get('/order/combinations', ServiceName::GENERIC_ORDER_COMBINATIONS, RouteName::ORDER_COMBINATIONS);
    $app->get('/order/mods', ServiceName::GENERIC_ORDER_MODS, RouteName::ORDER_MODS);
};
