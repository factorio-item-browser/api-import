<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Database;

use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the crafting factory service.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CraftingCategoryServiceFactory implements FactoryInterface
{
    /**
     * Creates the service.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return CraftingCategoryService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $container->get(CraftingCategoryRepository::class);

        return new CraftingCategoryService($craftingCategoryRepository);
    }
}
