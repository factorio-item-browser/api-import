<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Item as DatabaseItem;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\MissingItemException;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\ItemImporter;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\ExportData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the ItemImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\ItemImporter
 */
class ItemImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked id calculator.
     * @var IdCalculator&MockObject
     */
    protected $idCalculator;

    /**
     * The mocked item repository.
     * @var ItemRepository&MockObject
     */
    protected $itemRepository;

    /**
     * The mocked validator.
     * @var Validator&MockObject
     */
    protected $validator;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->itemRepository = $this->createMock(ItemRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new ItemImporter($this->idCalculator, $this->itemRepository, $this->validator);

        $this->assertSame($this->idCalculator, $this->extractProperty($importer, 'idCalculator'));
        $this->assertSame($this->itemRepository, $this->extractProperty($importer, 'itemRepository'));
    }

    /**
     * Tests the prepare method.
     * @covers ::prepare
     */
    public function testPrepare(): void
    {
        /* @var UuidInterface&MockObject $itemId1 */
        $itemId1 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $itemId2 */
        $itemId2 = $this->createMock(UuidInterface::class);

        /* @var ExportItem&MockObject $exportItem1 */
        $exportItem1 = $this->createMock(ExportItem::class);
        /* @var ExportItem&MockObject $exportItem2 */
        $exportItem2 = $this->createMock(ExportItem::class);

        /* @var ExportCombination&MockObject $combination */
        $combination = $this->createMock(ExportCombination::class);
        $combination->expects($this->once())
                    ->method('getItems')
                    ->willReturn([$exportItem1, $exportItem2]);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->once())
                   ->method('getCombination')
                   ->willReturn($combination);

        /* @var DatabaseItem&MockObject $databaseItem1 */
        $databaseItem1 = $this->createMock(DatabaseItem::class);
        $databaseItem1->expects($this->any())
                      ->method('getId')
                      ->willReturn($itemId1);

        /* @var DatabaseItem&MockObject $databaseItem2 */
        $databaseItem2 = $this->createMock(DatabaseItem::class);
        $databaseItem2->expects($this->any())
                      ->method('getId')
                      ->willReturn($itemId2);

        /* @var DatabaseItem&MockObject $existingDatabaseItem1 */
        $existingDatabaseItem1 = $this->createMock(DatabaseItem::class);
        /* @var DatabaseItem&MockObject $existingDatabaseItem2 */
        $existingDatabaseItem2 = $this->createMock(DatabaseItem::class);

        $this->itemRepository->expects($this->once())
                             ->method('findByIds')
                             ->with($this->identicalTo([$itemId1, $itemId2]))
                             ->willReturn([$existingDatabaseItem1, $existingDatabaseItem2]);

        /* @var ItemImporter&MockObject $importer */
        $importer = $this->getMockBuilder(ItemImporter::class)
                         ->onlyMethods(['map', 'add'])
                         ->setConstructorArgs([$this->idCalculator, $this->itemRepository, $this->validator])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('map')
                 ->withConsecutive(
                     [$this->identicalTo($exportItem1)],
                     [$this->identicalTo($exportItem2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseItem1,
                     $databaseItem2
                 );
        $importer->expects($this->exactly(4))
                 ->method('add')
                 ->withConsecutive(
                     [$databaseItem1],
                     [$databaseItem2],
                     [$existingDatabaseItem1],
                     [$existingDatabaseItem2]
                 );

        $importer->prepare($exportData);
    }

    /**
     * Tests the parse method.
     * @covers ::parse
     */
    public function testParse(): void
    {
        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);

        $importer = new ItemImporter($this->idCalculator, $this->itemRepository, $this->validator);
        $importer->parse($exportData);

        $this->addToAssertionCount(1);
    }

    /**
     * Tests the map method.
     * @throws ReflectionException
     * @covers ::map
     */
    public function testMap(): void
    {
        /* @var UuidInterface&MockObject $itemId */
        $itemId = $this->createMock(UuidInterface::class);

        $exportItem = new ExportItem();
        $exportItem->setType('abc')
                   ->setName('def');

        $expectedDatabaseItem = new DatabaseItem();
        $expectedDatabaseItem->setType('abc')
                             ->setName('def');

        $expectedResult = new DatabaseItem();
        $expectedResult->setId($itemId)
                       ->setType('abc')
                       ->setName('def');

        $this->idCalculator->expects($this->once())
                           ->method('calculateIdOfItem')
                           ->with($this->equalTo($expectedDatabaseItem))
                           ->willReturn($itemId);

        $this->validator->expects($this->once())
                        ->method('validateItem')
                        ->with($this->equalTo($expectedDatabaseItem));

        $importer = new ItemImporter($this->idCalculator, $this->itemRepository, $this->validator);
        $result = $this->invokeMethod($importer, 'map', $exportItem);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the add method.
     * @throws ReflectionException
     * @covers ::add
     */
    public function testAdd(): void
    {
        $item = new DatabaseItem();
        $item->setName('abc')
             ->setType('def');

        $expectedItems = [
            'def' => [
                'abc' => $item,
            ],
        ];

        $importer = new ItemImporter($this->idCalculator, $this->itemRepository, $this->validator);
        $this->invokeMethod($importer, 'add', $item);

        $this->assertSame($expectedItems, $this->extractProperty($importer, 'items'));
    }

    /**
     * Tests the getByTypeAndName method.
     * @throws ImportException
     * @throws ReflectionException
     * @covers ::getByTypeAndName
     */
    public function testGetByTypeAndName(): void
    {
        $type = 'abc';
        $name = 'def';

        /* @var DatabaseItem&MockObject $item */
        $item = $this->createMock(DatabaseItem::class);

        $items = [
            'abc' => [
                'def' => $item,
            ],
        ];

        $importer = new ItemImporter($this->idCalculator, $this->itemRepository, $this->validator);
        $this->injectProperty($importer, 'items', $items);

        $result = $importer->getByTypeAndName($type, $name);

        $this->assertSame($item, $result);
    }

    /**
     * Tests the getByTypeAndName method.
     * @throws ImportException
     * @covers ::getByTypeAndName
     */
    public function testGetByTypeAndNameWithoutMatch(): void
    {
        $type = 'abc';
        $name = 'def';

        $this->expectException(MissingItemException::class);

        $importer = new ItemImporter($this->idCalculator, $this->itemRepository, $this->validator);
        $importer->getByTypeAndName($type, $name);
    }

    /**
     * Tests the persist method.
     * @throws ReflectionException
     * @covers ::persist
     */
    public function testPersist(): void
    {
        /* @var DatabaseItem&MockObject $item1 */
        $item1 = $this->createMock(DatabaseItem::class);
        /* @var DatabaseItem&MockObject $item2 */
        $item2 = $this->createMock(DatabaseItem::class);
        /* @var DatabaseItem&MockObject $item3 */
        $item3 = $this->createMock(DatabaseItem::class);

        $items = [
            'abc' => [
                'def' => $item1,
                'ghi' => $item2,
            ],
            'jkl' => [
                'mno' => $item3,
            ],
        ];

        /* @var Collection&MockObject $itemCollection */
        $itemCollection = $this->createMock(Collection::class);
        $itemCollection->expects($this->once())
                       ->method('clear');
        $itemCollection->expects($this->exactly(3))
                       ->method('add')
                       ->withConsecutive(
                           [$this->identicalTo($item1)],
                           [$this->identicalTo($item2)],
                           [$this->identicalTo($item3)]
                       );

        /* @var DatabaseCombination&MockObject $combination */
        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->any())
                    ->method('getItems')
                    ->willReturn($itemCollection);

        /* @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(3))
                      ->method('persist')
                      ->withConsecutive(
                          [$this->identicalTo($item1)],
                          [$this->identicalTo($item2)],
                          [$this->identicalTo($item3)]
                      );

        $importer = new ItemImporter($this->idCalculator, $this->itemRepository, $this->validator);
        $this->injectProperty($importer, 'items', $items);

        $importer->persist($entityManager, $combination);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $this->itemRepository->expects($this->once())
                             ->method('removeOrphans');

        $importer = new ItemImporter($this->idCalculator, $this->itemRepository, $this->validator);
        $importer->cleanup();
    }
}
