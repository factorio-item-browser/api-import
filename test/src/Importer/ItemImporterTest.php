<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Item as DatabaseItem;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Importer\ItemImporter;
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
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->itemRepository = $this->createMock(ItemRepository::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new ItemImporter($this->idCalculator, $this->itemRepository);

        $this->assertSame($this->idCalculator, $this->extractProperty($importer, 'idCalculator'));
        $this->assertSame($this->itemRepository, $this->extractProperty($importer, 'itemRepository'));
    }

    /**
     * Tests the parse method.
     * @covers ::parse
     */
    public function testParse(): void
    {
        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);

        $importer = new ItemImporter($this->idCalculator, $this->itemRepository);
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

        $importer = new ItemImporter($this->idCalculator, $this->itemRepository);
        $result = $this->invokeMethod($importer, 'map', $exportItem);

        $this->assertEquals($expectedResult, $result);
    }
}
