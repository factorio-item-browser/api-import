<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Database;

use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the item service.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ItemServiceFactory implements FactoryInterface
{
    /**
     * Creates the service.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ItemService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var ItemRepository $itemRepository */
        $itemRepository = $container->get(ItemRepository::class);

        return new ItemService($itemRepository);
    }
}
