<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Item as DatabaseItem;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\ItemImporter;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
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
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

    /**
     * The mocked id calculator.
     * @var IdCalculator&MockObject
     */
    protected $idCalculator;

    /**
     * The mocked repository.
     * @var ItemRepository&MockObject
     */
    protected $repository;

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

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->repository = $this->createMock(ItemRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new ItemImporter($this->entityManager, $this->idCalculator, $this->repository, $this->validator);

        $this->assertSame($this->idCalculator, $this->extractProperty($importer, 'idCalculator'));
        $this->assertSame($this->validator, $this->extractProperty($importer, 'validator'));
    }

    /**
     * Tests the getCollectionFromCombination method.
     * @throws ReflectionException
     * @covers ::getCollectionFromCombination
     */
    public function testGetCollectionFromCombination(): void
    {
        $items = $this->createMock(Collection::class);

        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->once())
                    ->method('getItems')
                    ->willReturn($items);

        $importer = new ItemImporter($this->entityManager, $this->idCalculator, $this->repository, $this->validator);
        $result = $this->invokeMethod($importer, 'getCollectionFromCombination', $combination);

        $this->assertSame($items, $result);
    }

    /**
     * Tests the getExportEntities method.
     * @throws ReflectionException
     * @covers ::getExportEntities
     */
    public function testGetExportEntities(): void
    {
        $item1 = $this->createMock(ExportItem::class);
        $item2 = $this->createMock(ExportItem::class);
        $item3 = $this->createMock(ExportItem::class);

        $combination = new ExportCombination();
        $combination->setItems([$item1, $item2, $item3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new ItemImporter($this->entityManager, $this->idCalculator, $this->repository, $this->validator);
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals([$item1, $item2, $item3], iterator_to_array($result));
    }

    /**
     * Tests the createDatabaseEntity method.
     * @throws ReflectionException
     * @covers ::createDatabaseEntity
     */
    public function testCreateDatabaseEntity(): void
    {
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

        $importer = new ItemImporter($this->entityManager, $this->idCalculator, $this->repository, $this->validator);
        $result = $this->invokeMethod($importer, 'createDatabaseEntity', $exportItem);

        $this->assertEquals($expectedResult, $result);
    }
}
