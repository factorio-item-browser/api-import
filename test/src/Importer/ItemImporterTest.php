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
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the ItemImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\ItemImporter
 */
class ItemImporterTest extends TestCase
{
    use ReflectionTrait;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var IdCalculator&MockObject */
    private IdCalculator $idCalculator;
    /** @var ItemRepository&MockObject */
    private ItemRepository $repository;
    /** @var Validator&MockObject */
    private Validator $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->repository = $this->createMock(ItemRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return ItemImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): ItemImporter
    {
        return $this->getMockBuilder(ItemImporter::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->entityManager,
                        $this->idCalculator,
                        $this->repository,
                        $this->validator,
                    ])
                    ->getMock();
    }

    /**
     * @throws ReflectionException
     */
    public function testGetCollectionFromCombination(): void
    {
        $items = $this->createMock(Collection::class);

        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->once())
                    ->method('getItems')
                    ->willReturn($items);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getCollectionFromCombination', $combination);

        $this->assertSame($items, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetExportEntities(): void
    {
        $item1 = $this->createMock(ExportItem::class);
        $item2 = $this->createMock(ExportItem::class);
        $item3 = $this->createMock(ExportItem::class);

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getItems()->add($item1)
                               ->add($item2)
                               ->add($item3);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getExportEntities', $exportData);

        $this->assertEquals([$item1, $item2, $item3], iterator_to_array($result));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateDatabaseEntity(): void
    {
        $itemId = $this->createMock(UuidInterface::class);

        $exportItem = new ExportItem();
        $exportItem->type = 'abc';
        $exportItem->name = 'def';

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

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'createDatabaseEntity', $exportItem);

        $this->assertEquals($expectedResult, $result);
    }
}
