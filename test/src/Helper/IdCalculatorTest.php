<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Helper;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Entity\Machine;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Entity\Recipe;
use FactorioItemBrowser\Api\Database\Entity\RecipeIngredient;
use FactorioItemBrowser\Api\Database\Entity\RecipeProduct;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the IdCalculator class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Helper\IdCalculator
 */
class IdCalculatorTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the calculateIdOfCraftingCategory method.
     * @covers ::calculateIdOfCraftingCategory
     */
    public function testCalculateIdOfCraftingCategory(): void
    {
        $craftingCategory = new CraftingCategory();
        $craftingCategory->setName('abc');

        $expectedData = ['abc'];

        /* @var UuidInterface&MockObject $id */
        $id = $this->createMock(UuidInterface::class);

        /* @var IdCalculator&MockObject $helper */
        $helper = $this->getMockBuilder(IdCalculator::class)
                       ->onlyMethods(['calculateId'])
                       ->getMock();
        $helper->expects($this->once())
               ->method('calculateId')
               ->with($this->identicalTo($expectedData))
               ->willReturn($id);

        $result = $helper->calculateIdOfCraftingCategory($craftingCategory);

        $this->assertSame($id, $result);
    }

    /**
     * Tests the calculateIdOfItem method.
     * @covers ::calculateIdOfItem
     */
    public function testCalculateIdOfItem(): void
    {
        $item = new Item();
        $item->setType('abc')
             ->setName('def');

        $expectedData = ['abc', 'def'];

        /* @var UuidInterface&MockObject $id */
        $id = $this->createMock(UuidInterface::class);

        /* @var IdCalculator&MockObject $helper */
        $helper = $this->getMockBuilder(IdCalculator::class)
                       ->onlyMethods(['calculateId'])
                       ->getMock();
        $helper->expects($this->once())
               ->method('calculateId')
               ->with($this->identicalTo($expectedData))
               ->willReturn($id);

        $result = $helper->calculateIdOfItem($item);

        $this->assertSame($id, $result);
    }

    /**
     * Tests the calculateIdOfMachine method.
     * @covers ::calculateIdOfMachine
     */
    public function testCalculateIdOfMachine(): void
    {
        $craftingCategory1 = new CraftingCategory();
        $craftingCategory1->setId(Uuid::fromString('4ab1d86a-0151-4420-aca1-a491e8f44703'));
        $craftingCategory2 = new CraftingCategory();
        $craftingCategory2->setId(Uuid::fromString('24198ad4-c053-4781-81b8-ff87e2f80276'));

        $machine = new Machine();
        $machine->setName('abc')
                ->setCraftingSpeed(13.37)
                ->setNumberOfItemSlots(12)
                ->setNumberOfFluidInputSlots(34)
                ->setNumberOfFluidOutputSlots(56)
                ->setNumberOfModuleSlots(78)
                ->setEnergyUsage(2.1)
                ->setEnergyUsageUnit('def');
        $machine->getCraftingCategories()->add($craftingCategory1);
        $machine->getCraftingCategories()->add($craftingCategory2);

        $expectedData = [
            'abc',
            ['4ab1d86a-0151-4420-aca1-a491e8f44703', '24198ad4-c053-4781-81b8-ff87e2f80276'],
            13.37,
            12,
            34,
            56,
            78,
            2.1,
            'def',
        ];

        /* @var UuidInterface&MockObject $id */
        $id = $this->createMock(UuidInterface::class);

        /* @var IdCalculator&MockObject $helper */
        $helper = $this->getMockBuilder(IdCalculator::class)
                       ->onlyMethods(['calculateId'])
                       ->getMock();
        $helper->expects($this->once())
               ->method('calculateId')
               ->with($this->identicalTo($expectedData))
               ->willReturn($id);

        $result = $helper->calculateIdOfMachine($machine);

        $this->assertSame($id, $result);
    }

    /**
     * Tests the calculateIdOfMod method.
     * @covers ::calculateIdOfMod
     */
    public function testCalculateIdOfMod(): void
    {
        $mod = new Mod();
        $mod->setName('abc')
            ->setVersion('1.2.3');

        $expectedData = ['abc', '1.2.3'];

        /* @var UuidInterface&MockObject $id */
        $id = $this->createMock(UuidInterface::class);

        /* @var IdCalculator&MockObject $helper */
        $helper = $this->getMockBuilder(IdCalculator::class)
                       ->onlyMethods(['calculateId'])
                       ->getMock();
        $helper->expects($this->once())
               ->method('calculateId')
               ->with($this->identicalTo($expectedData))
               ->willReturn($id);

        $result = $helper->calculateIdOfMod($mod);

        $this->assertSame($id, $result);
    }

    /**
     * Tests the calculateIdOfRecipe method.
     * @covers ::calculateIdOfRecipe
     */
    public function testCalculateIdOfRecipe(): void
    {
        $ingredientItem1 = new Item();
        $ingredientItem1->setType('i1t')
                        ->setName('i1n');
        $ingredient1 = new RecipeIngredient();
        $ingredient1->setItem($ingredientItem1)
                    ->setAmount(1.2);

        $ingredientItem2 = new Item();
        $ingredientItem2->setType('i2t')
                        ->setName('i2n');
        $ingredient2 = new RecipeIngredient();
        $ingredient2->setItem($ingredientItem2)
                    ->setAmount(2.3);

        $productItem1 = new Item();
        $productItem1->setType('p1t')
                     ->setName('p1n');
        $product1 = new RecipeProduct();
        $product1->setItem($productItem1)
                 ->setAmountMin(3.4)
                 ->setAmountMax(4.5)
                 ->setProbability(5.6);

        $productItem2 = new Item();
        $productItem2->setType('p2t')
                     ->setName('p2n');
        $product2 = new RecipeProduct();
        $product2->setItem($productItem2)
                 ->setAmountMin(6.7)
                 ->setAmountMax(7.8)
                 ->setProbability(8.9);

        $recipe = new Recipe();
        $recipe->setName('abc')
               ->setMode('def');
        $recipe->getIngredients()->add($ingredient1);
        $recipe->getIngredients()->add($ingredient2);
        $recipe->getProducts()->add($product1);
        $recipe->getProducts()->add($product2);

        $expectedData = [
            'abc',
            'def',
            [
                ['i1t', 'i1n', 1.2],
                ['i2t', 'i2n', 2.3],
            ],
            [
                ['p1t', 'p1n', 3.4, 4.5, 5.6],
                ['p2t', 'p2n', 6.7, 7.8, 8.9],
            ],
        ];

        /* @var UuidInterface&MockObject $id */
        $id = $this->createMock(UuidInterface::class);

        /* @var IdCalculator&MockObject $helper */
        $helper = $this->getMockBuilder(IdCalculator::class)
                       ->onlyMethods(['calculateId'])
                       ->getMock();
        $helper->expects($this->once())
               ->method('calculateId')
               ->with($this->identicalTo($expectedData))
               ->willReturn($id);

        $result = $helper->calculateIdOfRecipe($recipe);
        $this->assertSame($id, $result);
    }


    /**
     * Tests the calculateIdOfTranslation method.
     * @covers ::calculateIdOfTranslation
     */
    public function testCalculateIdOfTranslation(): void
    {
        $translation = new Translation();
        $translation->setLocale('abc')
                    ->setType('def')
                    ->setName('ghi')
                    ->setValue('jkl')
                    ->setDescription('mno')
                    ->setIsDuplicatedByMachine(true)
                    ->setIsDuplicatedByRecipe(false);

        $expectedData = ['abc', 'def', 'ghi', 'jkl', 'mno', true, false];

        /* @var UuidInterface&MockObject $id */
        $id = $this->createMock(UuidInterface::class);

        /* @var IdCalculator&MockObject $helper */
        $helper = $this->getMockBuilder(IdCalculator::class)
                       ->onlyMethods(['calculateId'])
                       ->getMock();
        $helper->expects($this->once())
               ->method('calculateId')
               ->with($this->identicalTo($expectedData))
               ->willReturn($id);

        $result = $helper->calculateIdOfTranslation($translation);
        $this->assertSame($id, $result);
    }

    /**
     * Tests the calculateId method.
     * @throws ReflectionException
     * @covers ::calculateId
     */
    public function testCalculateId(): void
    {
        $data = ['abc', 'def'];
        $expectedResult = Uuid::fromString('9e86daa1-e1bd-94ed-176d-afd437e13d58');

        $helper = new IdCalculator();
        $result = $this->invokeMethod($helper, 'calculateId', $data);

        $this->assertEquals($expectedResult, $result);
    }
}
