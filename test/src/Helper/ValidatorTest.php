<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Helper;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Icon;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Entity\Machine;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Entity\Recipe;
use FactorioItemBrowser\Api\Database\Entity\RecipeIngredient;
use FactorioItemBrowser\Api\Database\Entity\RecipeProduct;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the Validator class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Helper\Validator
 */
class ValidatorTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the validateCraftingCategory method.
     * @covers ::validateCraftingCategory
     */
    public function testValidateCraftingCategory(): void
    {
        $craftingCategory = new CraftingCategory();
        $craftingCategory->setName('abc');

        $expectedCraftingCategory = new CraftingCategory();
        $expectedCraftingCategory->setName('cba');

        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['validateName'])
                          ->getMock();
        $validator->expects($this->once())
                  ->method('validateName')
                  ->with($this->identicalTo('abc'))
                  ->willReturn('cba');
        
        $validator->validateCraftingCategory($craftingCategory);
        
        $this->assertEquals($expectedCraftingCategory, $craftingCategory);
    }
    
    /**
     * Tests the validateIcon method.
     * @covers ::validateIcon
     */
    public function testValidateIcon(): void
    {
        $icon = new Icon();
        $icon->setName('abc');

        $expectedIcon = new Icon();
        $expectedIcon->setName('cba');

        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['validateName'])
                          ->getMock();
        $validator->expects($this->once())
                  ->method('validateName')
                  ->with($this->identicalTo('abc'))
                  ->willReturn('cba');
        
        $validator->validateIcon($icon);
        
        $this->assertEquals($expectedIcon, $icon);
    }
    
    /**
     * Tests the validateIconImage method.
     * @covers ::validateIconImage
     */
    public function testValidateIconImage(): void
    {
        $iconImage = new IconImage();
        $iconImage->setSize(42);

        $expectedIconImage = new IconImage();
        $expectedIconImage->setSize(24);

        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['limitInteger'])
                          ->getMock();
        $validator->expects($this->once())
                  ->method('limitInteger')
                  ->with($this->identicalTo(42), $this->identicalTo(0), $this->identicalTo(65535))
                  ->willReturn(24);

        $validator->validateIconImage($iconImage);

        $this->assertEquals($expectedIconImage, $iconImage);
    }

    /**
     * Tests the validateItem method.
     * @covers ::validateItem
     */
    public function testValidateItem(): void
    {
        $item = new Item();
        $item->setName('abc');

        $expectedItem = new Item();
        $expectedItem->setName('cba');
        
        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['validateName'])
                          ->getMock();
        $validator->expects($this->once())
                  ->method('validateName')
                  ->with($this->identicalTo('abc'))
                  ->willReturn('cba');
        
        $validator->validateItem($item);
        
        $this->assertEquals($expectedItem, $item);
    }
    
    /**
     * Tests the validateMachine method.
     * @covers ::validateMachine
     */
    public function testValidateMachine(): void
    {
        $machine = new Machine();
        $machine->setName('abc')
                ->setCraftingSpeed(13.37)
                ->setNumberOfItemSlots(12)
                ->setNumberOfFluidInputSlots(34)
                ->setNumberOfFluidOutputSlots(56)
                ->setNumberOfModuleSlots(78)
                ->setEnergyUsage(4.2);

        $expectedMachine = new Machine();
        $expectedMachine->setName('cba')
                        ->setCraftingSpeed(73.31)
                        ->setNumberOfItemSlots(21)
                        ->setNumberOfFluidInputSlots(43)
                        ->setNumberOfFluidOutputSlots(65)
                        ->setNumberOfModuleSlots(87)
                        ->setEnergyUsage(2.4);

        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['validateName', 'limitFloat', 'limitInteger'])
                          ->getMock();
        $validator->expects($this->once())
                  ->method('validateName')
                  ->with($this->identicalTo('abc'))
                  ->willReturn('cba');
        $validator->expects($this->exactly(2))
                  ->method('limitFloat')
                  ->withConsecutive(
                      [$this->identicalTo(13.37)],
                      [$this->identicalTo(4.2)]
                  )
                  ->willReturnOnConsecutiveCalls(
                      73.31,
                      2.4
                  );
        $validator->expects($this->exactly(4))
                  ->method('limitInteger')
                  ->withConsecutive(
                      [$this->identicalTo(12), $this->identicalTo(0), $this->identicalTo(255)],
                      [$this->identicalTo(34), $this->identicalTo(0), $this->identicalTo(255)],
                      [$this->identicalTo(56), $this->identicalTo(0), $this->identicalTo(255)],
                      [$this->identicalTo(78), $this->identicalTo(0), $this->identicalTo(255)]
                  )
                  ->willReturnOnConsecutiveCalls(
                      21,
                      43,
                      65,
                      87
                  );

        $validator->validateMachine($machine);

        $this->assertEquals($expectedMachine, $machine);
    }

    /**
     * Tests the validateMod method.
     * @covers ::validateMod
     */
    public function testValidateMod(): void
    {
        $mod = new Mod();
        $mod->setAuthor('abc')
            ->setVersion('1.2.3');

        $expectedMod = new Mod();
        $expectedMod->setAuthor('cba')
                    ->setVersion('3.2.1');

        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['limitString'])
                          ->getMock();
        $validator->expects($this->exactly(2))
                  ->method('limitString')
                  ->withConsecutive(
                      [$this->identicalTo('abc'), $this->identicalTo(255)],
                      [$this->identicalTo('1.2.3'), $this->identicalTo(16)]
                  )
                  ->willReturnOnConsecutiveCalls(
                      'cba',
                      '3.2.1'
                  );

        $validator->validateMod($mod);

        $this->assertEquals($expectedMod, $mod);
    }

    /**
     * Tests the validateRecipe method.
     * @covers ::validateRecipe
     */
    public function testValidateRecipe(): void
    {
        /* @var RecipeIngredient&MockObject $ingredient1 */
        $ingredient1 = $this->createMock(RecipeIngredient::class);
        /* @var RecipeIngredient&MockObject $ingredient2 */
        $ingredient2 = $this->createMock(RecipeIngredient::class);
        /* @var RecipeProduct&MockObject $product1 */
        $product1 = $this->createMock(RecipeProduct::class);
        /* @var RecipeProduct&MockObject $product2 */
        $product2 = $this->createMock(RecipeProduct::class);

        $recipe = new Recipe();
        $recipe->setName('abc')
               ->setCraftingTime(13.37);
        $recipe->getIngredients()->add($ingredient1);
        $recipe->getIngredients()->add($ingredient2);
        $recipe->getProducts()->add($product1);
        $recipe->getProducts()->add($product2);

        $expectedRecipe = new Recipe();
        $expectedRecipe->setName('cba')
                       ->setCraftingTime(73.31);
        $expectedRecipe->getIngredients()->add($ingredient1);
        $expectedRecipe->getIngredients()->add($ingredient2);
        $expectedRecipe->getProducts()->add($product1);
        $expectedRecipe->getProducts()->add($product2);

        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['validateName', 'limitFloat', 'validateIngredient', 'validateProduct'])
                          ->getMock();
        $validator->expects($this->once())
                  ->method('validateName')
                  ->with($this->identicalTo('abc'))
                  ->willReturn('cba');
        $validator->expects($this->once())
                  ->method('limitFloat')
                  ->with($this->identicalTo(13.37))
                  ->willReturn(73.31);
        $validator->expects($this->exactly(2))
                  ->method('validateIngredient')
                  ->withConsecutive(
                      [$this->identicalTo($ingredient1)],
                      [$this->identicalTo($ingredient2)]
                  );
        $validator->expects($this->exactly(2))
                  ->method('validateProduct')
                  ->withConsecutive(
                      [$this->identicalTo($product1)],
                      [$this->identicalTo($product2)]
                  );

        $validator->validateRecipe($recipe);

        $this->assertEquals($expectedRecipe, $recipe);
    }

    /**
     * Tests the validateIngredient method.
     * @throws ReflectionException
     * @covers ::validateIngredient
     */
    public function testValidateIngredient(): void
    {
        $ingredient = new RecipeIngredient();
        $ingredient->setOrder(12)
                   ->setAmount(3.4);

        $expectedIngredient = new RecipeIngredient();
        $expectedIngredient->setOrder(21)
                           ->setAmount(4.3);

        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['limitInteger', 'limitFloat'])
                          ->getMock();
        $validator->expects($this->once())
                  ->method('limitInteger')
                  ->with($this->identicalTo(12), $this->identicalTo(0), $this->identicalTo(255))
                  ->willReturn(21);
        $validator->expects($this->once())
                  ->method('limitFloat')
                  ->with($this->identicalTo(3.4))
                  ->willReturn(4.3);

        $this->invokeMethod($validator, 'validateIngredient', $ingredient);

        $this->assertEquals($expectedIngredient, $ingredient);
    }
    
    /**
     * Tests the validateProduct method.
     * @throws ReflectionException
     * @covers ::validateProduct
     */
    public function testValidateProduct(): void
    {
        $product = new RecipeProduct();
        $product->setOrder(12)
                ->setAmountMin(3.4)
                ->setAmountMax(5.6)
                ->setProbability(7.8);

        $expectedProduct = new RecipeProduct();
        $expectedProduct->setOrder(21)
                        ->setAmountMin(4.3)
                        ->setAmountMax(6.5)
                        ->setProbability(8.7);

        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['limitInteger', 'limitFloat'])
                          ->getMock();
        $validator->expects($this->once())
                  ->method('limitInteger')
                  ->with($this->identicalTo(12), $this->identicalTo(0), $this->identicalTo(255))
                  ->willReturn(21);
        $validator->expects($this->exactly(3))
                  ->method('limitFloat')
                  ->withConsecutive(
                      [$this->identicalTo(3.4)],
                      [$this->identicalTo(5.6)],
                      [$this->identicalTo(7.8)]
                  )
                  ->willReturnOnConsecutiveCalls(
                      4.3,
                      6.5,
                      8.7
                  );

        $this->invokeMethod($validator, 'validateProduct', $product);

        $this->assertEquals($expectedProduct, $product);
    }

    /**
     * Tests the validateName method.
     * @throws ReflectionException
     * @covers ::validateName
     */
    public function testValidateName(): void
    {
        $name = 'Abc';
        $limitedName = 'Def';
        $expectedResult = 'def';

        /* @var Validator&MockObject $validator */
        $validator = $this->getMockBuilder(Validator::class)
                          ->onlyMethods(['limitString'])
                          ->getMock();
        $validator->expects($this->once())
                  ->method('limitString')
                  ->with($this->identicalTo($name))
                  ->willReturn($limitedName);

        $result = $this->invokeMethod($validator, 'validateName', $name);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the limitString test.
     * @return array<mixed>
     */
    public function provideLimitString(): array
    {
        return [
            ['foo', 32, 'foo'],
            [' foo ', 32, 'foo'],
            [' foo-bar ', 3, 'foo'],
        ];
    }

    /**
     * Tests the limitString method.
     * @param string $string
     * @param int $maxLength
     * @param string $expectedResult
     * @throws ReflectionException
     * @covers ::limitString
     * @dataProvider provideLimitString
     */
    public function testLimitString(string $string, int $maxLength, string $expectedResult): void
    {
        $validator = new Validator();
        $result = $this->invokeMethod($validator, 'limitString', $string, $maxLength);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the limitInteger test.
     * @return array<mixed>
     */
    public function provideLimitInteger(): array
    {
        return [
            [42, 0, 255, 42],
            [1024, 0, 255, 255],
            [-42, 0, 255, 0],
            [-42, -128, 127, -42],
        ];
    }

    /**
     * Tests the limitInteger method.
     * @param int $integer
     * @param int $minValue
     * @param int $maxValue
     * @param int $expectedResult
     * @throws ReflectionException
     * @covers ::limitInteger
     * @dataProvider provideLimitInteger
     */
    public function testLimitInteger(int $integer, int $minValue, int $maxValue, int $expectedResult): void
    {
        $validator = new Validator();
        $result = $this->invokeMethod($validator, 'limitInteger', $integer, $minValue, $maxValue);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the limitFloat test.
     * @return array<mixed>
     */
    public function provideLimitFloat(): array
    {
        return [
            [13.37, 13.37],
            [-13.37, 0],
            [pow(2, 50), 4294967.295],
        ];
    }

    /**
     * Tests the limitFloat method.
     * @param float $float
     * @param float $expectedResult
     * @throws ReflectionException
     * @covers ::limitFloat
     * @dataProvider provideLimitFloat
     */
    public function testLimitFloat(float $float, float $expectedResult): void
    {
        $validator = new Validator();
        $result = $this->invokeMethod($validator, 'limitFloat', $float);

        $this->assertSame($expectedResult, $result);
    }
}
