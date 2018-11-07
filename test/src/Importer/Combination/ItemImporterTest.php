<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Item as DatabaseItem;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\ItemImporter;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the ItemImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Combination\ItemImporter
 */
class ItemImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ItemRepository $itemRepository */
        $itemRepository = $this->createMock(ItemRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new ItemImporter($entityManager, $itemRepository, $registryService);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($itemRepository, $this->extractProperty($importer, 'itemRepository'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        $newItems = [
            $this->createMock(DatabaseItem::class),
            $this->createMock(DatabaseItem::class),
        ];
        $existingItems = [
            $this->createMock(DatabaseItem::class),
            $this->createMock(DatabaseItem::class),
        ];
        $persistedItems = [
            $this->createMock(DatabaseItem::class),
            $this->createMock(DatabaseItem::class),
        ];

        /* @var ExportCombination $exportCombination */
        $exportCombination = $this->createMock(ExportCombination::class);
        /* @var Collection $itemCollection */
        $itemCollection = $this->createMock(Collection::class);

        /* @var DatabaseCombination|MockObject $databaseCombination */
        $databaseCombination = $this->getMockBuilder(DatabaseCombination::class)
                                    ->setMethods(['getItems'])
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $databaseCombination->expects($this->once())
                            ->method('getItems')
                            ->willReturn($itemCollection);

        /* @var ItemImporter|MockObject $importer */
        $importer = $this->getMockBuilder(ItemImporter::class)
                         ->setMethods([
                             'getItemsFromCombination',
                             'getExistingItems',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getItemsFromCombination')
                 ->with($exportCombination)
                 ->willReturn($newItems);
        $importer->expects($this->once())
                 ->method('getExistingItems')
                 ->with($newItems)
                 ->willReturn($existingItems);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newItems, $existingItems)
                 ->willReturn($persistedItems);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedItems, $itemCollection);

        $importer->import($exportCombination, $databaseCombination);
    }

    /**
     * Tests the getItemsFromCombination method.
     * @throws ReflectionException
     * @covers ::getItemsFromCombination
     */
    public function testGetItemsFromCombination(): void
    {
        /* @var ExportItem $exportItem1 */
        $exportItem1 = $this->createMock(ExportItem::class);
        /* @var ExportItem $exportItem2 */
        $exportItem2 = $this->createMock(ExportItem::class);
        /* @var DatabaseItem $databaseItem1 */
        $databaseItem1 = $this->createMock(DatabaseItem::class);
        /* @var DatabaseItem $databaseItem2 */
        $databaseItem2 = $this->createMock(DatabaseItem::class);

        $itemHashes = ['abc', 'def'];
        $expectedResult = [
            'ghi' => $databaseItem1,
            'jkl' => $databaseItem2,
        ];

        /* @var ExportCombination|MockObject $exportCombination */
        $exportCombination = $this->getMockBuilder(ExportCombination::class)
                                  ->setMethods(['getItemHashes'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportCombination->expects($this->once())
                          ->method('getItemHashes')
                          ->willReturn($itemHashes);

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getItem'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(2))
                        ->method('getItem')
                        ->withConsecutive(
                            ['abc'],
                            ['def']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $exportItem1,
                            $exportItem2
                        );

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ItemRepository $itemRepository */
        $itemRepository = $this->createMock(ItemRepository::class);

        /* @var ItemImporter|MockObject $importer */
        $importer = $this->getMockBuilder(ItemImporter::class)
                         ->setMethods(['mapItem', 'getIdentifier'])
                         ->setConstructorArgs([$entityManager, $itemRepository, $registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('mapItem')
                 ->withConsecutive(
                     [$exportItem1],
                     [$exportItem2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseItem1,
                     $databaseItem2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$databaseItem1],
                     [$databaseItem2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'ghi',
                     'jkl'
                 );

        $result = $this->invokeMethod($importer, 'getItemsFromCombination', $exportCombination);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the mapItem method.
     * @throws ReflectionException
     * @covers ::mapItem
     */
    public function testMapItem(): void
    {
        $exportItem = new ExportItem();
        $exportItem->setType('abc')
                   ->setName('def');

        $expectedResult = new DatabaseItem('abc', 'def');

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ItemRepository $itemRepository */
        $itemRepository = $this->createMock(ItemRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new ItemImporter($entityManager, $itemRepository, $registryService);
        $result = $this->invokeMethod($importer, 'mapItem', $exportItem);

        $this->assertEquals($expectedResult, $result);
    }

}
