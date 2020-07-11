<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Helper;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Collection\NamesByTypes;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Database\Repository\IconImageRepository;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Exception\MissingCraftingCategoryException;
use FactorioItemBrowser\Api\Import\Exception\MissingIconImageException;
use FactorioItemBrowser\Api\Import\Exception\MissingItemException;
use FactorioItemBrowser\Api\Import\Helper\DataCollector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the DataCollector class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Helper\DataCollector
 */
class DataCollectorTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked crafting category repository.
     * @var CraftingCategoryRepository&MockObject
     */
    protected $craftingCategoryRepository;

    /**
     * The mocked icon image repository.
     * @var IconImageRepository&MockObject
     */
    protected $iconImageRepository;

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

        $this->craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);
        $this->iconImageRepository = $this->createMock(IconImageRepository::class);
        $this->itemRepository = $this->createMock(ItemRepository::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $collector = new DataCollector(
            $this->craftingCategoryRepository,
            $this->iconImageRepository,
            $this->itemRepository,
        );

        $this->assertSame(
            $this->craftingCategoryRepository,
            $this->extractProperty($collector, 'craftingCategoryRepository'),
        );
        $this->assertSame($this->iconImageRepository, $this->extractProperty($collector, 'iconImageRepository'));
        $this->assertSame($this->itemRepository, $this->extractProperty($collector, 'itemRepository'));
        $this->assertInstanceOf(NamesByTypes::class, $this->extractProperty($collector, 'itemTypesAndNames'));
    }

    /**
     * Tests the setCombination method.
     * @throws ReflectionException
     * @covers ::setCombination
     */
    public function testSetCombination(): void
    {
        $combination = $this->createMock(Combination::class);

        $collector = new DataCollector(
            $this->craftingCategoryRepository,
            $this->iconImageRepository,
            $this->itemRepository,
        );
        $result = $collector->setCombination($combination);

        $this->assertSame($collector, $result);
        $this->assertSame($combination, $this->extractProperty($collector, 'combination'));
    }

    /**
     * Tests the addCraftingCategoryName method.
     * @throws ReflectionException
     * @covers ::addCraftingCategoryName
     */
    public function testAddCraftingCategoryName(): void
    {
        $craftingCategoryName = 'abc';
        $craftingCategoryNames = [
            'def' => true,
            'ghi' => true,
        ];
        $expectedCraftingCategoryNames = [
            'def' => true,
            'ghi' => true,
            'abc' => true,
        ];

        $collector = new DataCollector(
            $this->craftingCategoryRepository,
            $this->iconImageRepository,
            $this->itemRepository,
        );
        $this->injectProperty($collector, 'craftingCategoryNames', $craftingCategoryNames);

        $result = $collector->addCraftingCategoryName($craftingCategoryName);

        $this->assertSame($collector, $result);
        $this->assertSame($expectedCraftingCategoryNames, $this->extractProperty($collector, 'craftingCategoryNames'));
    }

    /**
     * Tests the getCraftingCategory method.
     * @throws MissingCraftingCategoryException
     * @throws ReflectionException
     * @covers ::getCraftingCategory
     */
    public function testGetCraftingCategory(): void
    {
        $name = 'def';
        $craftingCategory1 = $this->createMock(CraftingCategory::class);
        $craftingCategory2 = $this->createMock(CraftingCategory::class);
        $craftingCategories = [
            'abc' => $craftingCategory1,
            'def' => $craftingCategory2,
        ];
        $expectedResult = $craftingCategory2;

        $collector = $this->getMockBuilder(DataCollector::class)
                          ->onlyMethods(['fetchCraftingCategories'])
                          ->setConstructorArgs([
                              $this->craftingCategoryRepository,
                              $this->iconImageRepository,
                              $this->itemRepository,
                          ])
                          ->getMock();
        $collector->expects($this->once())
                  ->method('fetchCraftingCategories');
        $this->injectProperty($collector, 'craftingCategories', $craftingCategories);

        $result = $collector->getCraftingCategory($name);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getCraftingCategory method.
     * @throws MissingCraftingCategoryException
     * @covers ::getCraftingCategory
     */
    public function testGetCraftingCategoryWithoutMatch(): void
    {
        $name = 'abc';

        $this->expectException(MissingCraftingCategoryException::class);

        $collector = $this->getMockBuilder(DataCollector::class)
                          ->onlyMethods(['fetchCraftingCategories'])
                          ->setConstructorArgs([
                              $this->craftingCategoryRepository,
                              $this->iconImageRepository,
                              $this->itemRepository,
                          ])
                          ->getMock();
        $collector->expects($this->once())
                  ->method('fetchCraftingCategories');

        $collector->getCraftingCategory($name);
    }

    /**
     * Tests the fetchCraftingCategories method.
     * @throws ReflectionException
     * @covers ::fetchCraftingCategories
     */
    public function testFetchCraftingCategories(): void
    {
        $craftingCategoryNames = [
            'abc' => true,
            'def' => true,
        ];

        $craftingCategory1 = new CraftingCategory();
        $craftingCategory1->setName('abc');
        $craftingCategory2 = new CraftingCategory();
        $craftingCategory2->setName('def');

        $craftingCategory3 = $this->createMock(CraftingCategory::class);

        $craftingCategories = [
            'foo' => $craftingCategory3,
        ];
        $expectedCraftingCategories = [
            'foo' => $craftingCategory3,
            'abc' => $craftingCategory1,
            'def' => $craftingCategory2,
        ];
        
        $this->craftingCategoryRepository->expects($this->once())
                                         ->method('findByNames')
                                         ->with($this->identicalTo(['abc', 'def']))
                                         ->willReturn([$craftingCategory1, $craftingCategory2]);

        $collector = new DataCollector(
            $this->craftingCategoryRepository,
            $this->iconImageRepository,
            $this->itemRepository,
        );
        $this->injectProperty($collector, 'craftingCategoryNames', $craftingCategoryNames);
        $this->injectProperty($collector, 'craftingCategories', $craftingCategories);

        $this->invokeMethod($collector, 'fetchCraftingCategories');

        $this->assertSame([], $this->extractProperty($collector, 'craftingCategoryNames'));
        $this->assertSame($expectedCraftingCategories, $this->extractProperty($collector, 'craftingCategories'));
    }

    /**
     * Tests the addIconImageId method.
     * @throws ReflectionException
     * @covers ::addIconImageId
     */
    public function testAddIconImageId(): void
    {
        $iconImageId = '0f6581b2-dce1-4d32-92d7-f10e8b0cfefe';
        $iconImageIds = [
            '10629cbb-027c-4168-8802-c5bf0e62e99f' => Uuid::fromString('10629cbb-027c-4168-8802-c5bf0e62e99f'),
            '2fb7866b-11de-49cc-811b-c07f92feeea4' => Uuid::fromString('2fb7866b-11de-49cc-811b-c07f92feeea4'),
        ];
        $expectedIconImageIds = [
            '10629cbb-027c-4168-8802-c5bf0e62e99f' => Uuid::fromString('10629cbb-027c-4168-8802-c5bf0e62e99f'),
            '2fb7866b-11de-49cc-811b-c07f92feeea4' => Uuid::fromString('2fb7866b-11de-49cc-811b-c07f92feeea4'),
            '0f6581b2-dce1-4d32-92d7-f10e8b0cfefe' => Uuid::fromString('0f6581b2-dce1-4d32-92d7-f10e8b0cfefe'),
        ];

        $collector = new DataCollector(
            $this->craftingCategoryRepository,
            $this->iconImageRepository,
            $this->itemRepository,
        );
        $this->injectProperty($collector, 'iconImageIds', $iconImageIds);

        $result = $collector->addIconImageId($iconImageId);

        $this->assertSame($collector, $result);
        $this->assertEquals($expectedIconImageIds, $this->extractProperty($collector, 'iconImageIds'));
    }

    /**
     * Tests the getIconImage method.
     * @throws MissingIconImageException
     * @throws ReflectionException
     * @covers ::getIconImage
     */
    public function testGetIconImage(): void
    {
        $iconImageId = 'def';

        $iconImage1 = $this->createMock(IconImage::class);
        $iconImage2 = $this->createMock(IconImage::class);
        $iconImages = [
            'abc' => $iconImage1,
            'def' => $iconImage2,
        ];
        $expectedResult = $iconImage2;

        $collector = $this->getMockBuilder(DataCollector::class)
                          ->onlyMethods(['fetchIconImages'])
                          ->setConstructorArgs([
                              $this->craftingCategoryRepository,
                              $this->iconImageRepository,
                              $this->itemRepository,
                          ])
                          ->getMock();
        $collector->expects($this->once())
                  ->method('fetchIconImages');
        $this->injectProperty($collector, 'iconImages', $iconImages);

        $result = $collector->getIconImage($iconImageId);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getIconImage method.
     * @throws MissingIconImageException
     * @covers ::getIconImage
     */
    public function testGetIconImageWithoutMatch(): void
    {
        $iconImageId = 'def';

        $this->expectException(MissingIconImageException::class);

        $collector = $this->getMockBuilder(DataCollector::class)
                          ->onlyMethods(['fetchIconImages'])
                          ->setConstructorArgs([
                              $this->craftingCategoryRepository,
                              $this->iconImageRepository,
                              $this->itemRepository,
                          ])
                          ->getMock();
        $collector->expects($this->once())
                  ->method('fetchIconImages');

        $collector->getIconImage($iconImageId);
    }

    /**
     * Tests the fetchIconImages method.
     * @throws ReflectionException
     * @covers ::fetchIconImages
     */
    public function testFetchIconImages(): void
    {
        $iconImageIds = [
            '10629cbb-027c-4168-8802-c5bf0e62e99f' => Uuid::fromString('10629cbb-027c-4168-8802-c5bf0e62e99f'),
            '2fb7866b-11de-49cc-811b-c07f92feeea4' => Uuid::fromString('2fb7866b-11de-49cc-811b-c07f92feeea4'),
        ];
        $expectedIconImageIds = [
            Uuid::fromString('10629cbb-027c-4168-8802-c5bf0e62e99f'),
            Uuid::fromString('2fb7866b-11de-49cc-811b-c07f92feeea4'),
        ];

        $iconImage1 = new IconImage();
        $iconImage1->setId(Uuid::fromString('10629cbb-027c-4168-8802-c5bf0e62e99f'));
        $iconImage2 = new IconImage();
        $iconImage2->setId(Uuid::fromString('2fb7866b-11de-49cc-811b-c07f92feeea4'));

        $iconImage3 = $this->createMock(IconImage::class);

        $iconImages = [
            '0f6581b2-dce1-4d32-92d7-f10e8b0cfefe' => $iconImage3,
        ];
        $expectedIconImages = [
            '0f6581b2-dce1-4d32-92d7-f10e8b0cfefe' => $iconImage3,
            '10629cbb-027c-4168-8802-c5bf0e62e99f' => $iconImage1,
            '2fb7866b-11de-49cc-811b-c07f92feeea4' => $iconImage2,
        ];

        $this->iconImageRepository->expects($this->once())
                                  ->method('findByIds')
                                  ->with($this->equalTo($expectedIconImageIds))
                                  ->willReturn([$iconImage1, $iconImage2]);

        $collector = new DataCollector(
            $this->craftingCategoryRepository,
            $this->iconImageRepository,
            $this->itemRepository,
        );
        $this->injectProperty($collector, 'iconImageIds', $iconImageIds);
        $this->injectProperty($collector, 'iconImages', $iconImages);

        $this->invokeMethod($collector, 'fetchIconImages');

        $this->assertSame([], $this->extractProperty($collector, 'iconImageIds'));
        $this->assertSame($expectedIconImages, $this->extractProperty($collector, 'iconImages'));
    }

    /**
     * Tests the addItem method.
     * @throws ReflectionException
     * @covers ::addItem
     */
    public function testAddItem(): void
    {
        $type = 'abc';
        $name = 'def';

        $itemTypesAndNames = $this->createMock(NamesByTypes::class);
        $itemTypesAndNames->expects($this->once())
                          ->method('addName')
                          ->with($this->identicalTo($type), $this->identicalTo($name));

        $collector = new DataCollector(
            $this->craftingCategoryRepository,
            $this->iconImageRepository,
            $this->itemRepository,
        );
        $this->injectProperty($collector, 'itemTypesAndNames', $itemTypesAndNames);

        $result = $collector->addItem($type, $name);

        $this->assertSame($collector, $result);
    }

    /**
     * Tests the getItem method.
     * @throws MissingItemException
     * @throws ReflectionException
     * @covers ::getItem
     */
    public function testGetItem(): void
    {
        $type = 'def';
        $name = 'ghi';

        $item1 = $this->createMock(Item::class);
        $item2 = $this->createMock(Item::class);
        $item3 = $this->createMock(Item::class);

        $items = [
            'abc' => [
                'ghi' => $item1
            ],
            'def' => [
                'ghi' => $item2,
                'abc' => $item3,
            ],
        ];
        $expectedResult = $item2;

        $collector = $this->getMockBuilder(DataCollector::class)
                          ->onlyMethods(['fetchItems'])
                          ->setConstructorArgs([
                              $this->craftingCategoryRepository,
                              $this->iconImageRepository,
                              $this->itemRepository,
                          ])
                          ->getMock();
        $collector->expects($this->once())
                  ->method('fetchItems');
        $this->injectProperty($collector, 'items', $items);

        $result = $collector->getItem($type, $name);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getItem method.
     * @throws MissingItemException
     * @covers ::getItem
     */
    public function testGetItemWithoutMatch(): void
    {
        $type = 'def';
        $name = 'ghi';

        $this->expectException(MissingItemException::class);

        $collector = $this->getMockBuilder(DataCollector::class)
                          ->onlyMethods(['fetchItems'])
                          ->setConstructorArgs([
                              $this->craftingCategoryRepository,
                              $this->iconImageRepository,
                              $this->itemRepository,
                          ])
                          ->getMock();
        $collector->expects($this->once())
                  ->method('fetchItems');

        $collector->getItem($type, $name);
    }
    
    /**
     * Tests the fetchItems method.
     * @throws ReflectionException
     * @covers ::fetchItems
     */
    public function testFetchItems(): void
    {
        $combinationId = $this->createMock(UuidInterface::class);
        $combination = new Combination();
        $combination->setId($combinationId);

        $itemTypesAndNames = $this->createMock(NamesByTypes::class);
        $itemTypesAndNames->expects($this->once())
                          ->method('isEmpty')
                          ->willReturn(false);
        
        $item1 = new Item();
        $item1->setType('abc')
              ->setName('def');
        $item2 = new Item();
        $item2->setType('ghi')
              ->setName('jkl');  
        $item3 = $this->createMock(Item::class);

        $items = [
            'abc' => [
                'foo' => $item3,
            ],
        ];
        $expectedItems = [
            'abc' => [
                'foo' => $item3,
                'def' => $item1,
            ],
            'ghi' => [
                'jkl' => $item2,
            ],
        ];
        
        $this->itemRepository->expects($this->once())
                             ->method('findByTypesAndNames')
                             ->with($this->identicalTo($combinationId), $this->identicalTo($itemTypesAndNames))
                             ->willReturn([$item1, $item2]);

        $collector = new DataCollector(
            $this->craftingCategoryRepository,
            $this->iconImageRepository,
            $this->itemRepository,
        );
        $this->injectProperty($collector, 'combination', $combination);
        $this->injectProperty($collector, 'itemTypesAndNames', $itemTypesAndNames);
        $this->injectProperty($collector, 'items', $items);

        $this->invokeMethod($collector, 'fetchItems');

        $this->assertNotSame($itemTypesAndNames, $this->extractProperty($collector, 'itemTypesAndNames'));
        $this->assertSame($expectedItems, $this->extractProperty($collector, 'items'));
    }
}
