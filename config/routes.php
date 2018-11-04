<?php

declare(strict_types=1);

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
    $app->get('/mod/{modName}', Handler\ModHandler::class, RouteName::MOD);
    $app->get('/mod/{modName}/translations', ServiceName::MOD_TRANSLATIONS_HANDLER, RouteName::MOD_TRANSLATIONS);
};
