<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\ExportData\Service\ExportDataService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the item importer.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ItemImporterFactory implements FactoryInterface
{
    /**
     * Creates the importer manager.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ItemImporter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /* @var ExportDataService $exportDataService */
        $exportDataService = $container->get(ExportDataService::class);
        /* @var ItemRepository $itemRepository */
        $itemRepository = $entityManager->getRepository(Item::class);

        return new ItemImporter($entityManager, $exportDataService->getItemRegistry(), $itemRepository);
    }
}
