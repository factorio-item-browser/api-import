<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\RecipeIngredient as DatabaseIngredient;
use FactorioItemBrowser\Api\Database\Entity\RecipeProduct as DatabaseProduct;
use FactorioItemBrowser\Api\Database\Entity\Recipe as DatabaseRecipe;
use FactorioItemBrowser\Api\Import\Helper\DataCollector;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\RecipeImporter;
use FactorioItemBrowser\ExportData\Entity\Recipe\Ingredient as ExportIngredient;
use FactorioItemBrowser\ExportData\Entity\Recipe\Product as ExportProduct;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the RecipeImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\RecipeImporter
 */
class RecipeImporterTest extends TestCase
{
    use ReflectionTrait;

    /** @var DataCollector&MockObject */
    private DataCollector $dataCollector;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var IdCalculator&MockObject */
    private IdCalculator $idCalculator;
    /** @var RecipeRepository&MockObject */
    private RecipeRepository $repository;
    /** @var Validator&MockObject */
    private Validator $validator;

    protected function setUp(): void
    {
        $this->dataCollector = $this->createMock(DataCollector::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->repository = $this->createMock(RecipeRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return RecipeImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): RecipeImporter
    {
        return $this->getMockBuilder(RecipeImporter::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->dataCollector,
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
        $recipes = $this->createMock(Collection::class);

        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->once())
                    ->method('getRecipes')
                    ->willReturn($recipes);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getCollectionFromCombination', $combination);

        $this->assertSame($recipes, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testPrepareImport(): void
    {
        $combination = $this->createMock(DatabaseCombination::class);
        $exportData = $this->createMock(ExportData::class);
        $offset = 1337;
        $limit = 42;

        $this->dataCollector->expects($this->once())
                            ->method('setCombination')
                            ->with($this->identicalTo($combination));

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'prepareImport', $combination, $exportData, $offset, $limit);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetExportEntities(): void
    {
        $ingredient1 = new ExportIngredient();
        $ingredient1->type = 'ghi';
        $ingredient1->name = 'jkl';
        $ingredient2 = new ExportIngredient();
        $ingredient2->type = 'mno';
        $ingredient2->name = 'pqr';
        $product1 = new ExportProduct();
        $product1->type = 'stu';
        $product1->name = 'vwx';
        $product2 = new ExportProduct();
        $product2->type = 'yza';
        $product2->name = 'bcd';

        $recipe1 = new ExportRecipe();
        $recipe1->craftingCategory = 'abc';
        $recipe1->ingredients = [$ingredient1, $ingredient2];
        $recipe1->products = [$product1];
        $recipe2 = new ExportRecipe();
        $recipe2->craftingCategory = 'def';
        $recipe2->products = [$product2];
        $recipe3 = new ExportRecipe();
        $recipe3->craftingCategory = 'abc';
        $recipe3->ingredients = [$ingredient1];
        $recipe3->products = [$product1];

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getRecipes()->add($recipe1)
                                 ->add($recipe2)
                                 ->add($recipe3);

        $this->dataCollector->expects($this->exactly(3))
                            ->method('addCraftingCategoryName')
                            ->withConsecutive(
                                [$this->identicalTo('abc')],
                                [$this->identicalTo('def')],
                                [$this->identicalTo('abc')],
                            );
        $this->dataCollector->expects($this->exactly(6))
                            ->method('addItem')
                            ->withConsecutive(
                                [$this->identicalTo('ghi'), $this->identicalTo('jkl')],
                                [$this->identicalTo('mno'), $this->identicalTo('pqr')],
                                [$this->identicalTo('stu'), $this->identicalTo('vwx')],
                                [$this->identicalTo('yza'), $this->identicalTo('bcd')],
                                [$this->identicalTo('ghi'), $this->identicalTo('jkl')],
                                [$this->identicalTo('stu'), $this->identicalTo('vwx')],
                            );

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getExportEntities', $exportData);

        $this->assertEquals([$recipe1, $recipe2, $recipe3], iterator_to_array($result));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateDatabaseEntity(): void
    {
        $recipeId = $this->createMock(UuidInterface::class);
        $craftingCategory = $this->createMock(CraftingCategory::class);

        $exportRecipe = new ExportRecipe();
        $exportRecipe->name = 'abc';
        $exportRecipe->mode = 'def';
        $exportRecipe->craftingCategory = 'ghi';
        $exportRecipe->craftingTime = 13.37;

        $expectedDatabaseRecipe = new DatabaseRecipe();
        $expectedDatabaseRecipe->setName('abc')
                               ->setMode('def')
                               ->setCraftingCategory($craftingCategory)
                               ->setCraftingTime(13.37);

        $expectedResult = new DatabaseRecipe();
        $expectedResult->setId($recipeId)
                       ->setName('abc')
                       ->setMode('def')
                       ->setCraftingCategory($craftingCategory)
                       ->setCraftingTime(13.37);

        $this->dataCollector->expects($this->once())
                            ->method('getCraftingCategory')
                            ->with($this->identicalTo('ghi'))
                            ->willReturn($craftingCategory);

        $this->idCalculator->expects($this->once())
                           ->method('calculateIdOfRecipe')
                           ->with($this->equalTo($expectedDatabaseRecipe))
                           ->willReturn($recipeId);

        $this->validator->expects($this->once())
                        ->method('validateRecipe')
                        ->with($this->equalTo($expectedDatabaseRecipe));

        $instance = $this->createInstance(['mapIngredients', 'mapProducts']);
        $instance->expects($this->once())
                 ->method('mapIngredients')
                 ->with($this->identicalTo($exportRecipe), $this->equalTo($expectedDatabaseRecipe));
        $instance->expects($this->once())
                 ->method('mapProducts')
                 ->with($this->identicalTo($exportRecipe), $this->equalTo($expectedDatabaseRecipe));

        $result = $this->invokeMethod($instance, 'createDatabaseEntity', $exportRecipe);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testMapIngredients(): void
    {
        $exportIngredient1 = $this->createMock(ExportIngredient::class);
        $exportIngredient2 = $this->createMock(ExportIngredient::class);

        $exportRecipe = new ExportRecipe();
        $exportRecipe->ingredients = [$exportIngredient1, $exportIngredient2];

        $databaseRecipe = $this->createMock(DatabaseRecipe::class);

        $databaseIngredient1 = $this->createMock(DatabaseIngredient::class);
        $databaseIngredient1->expects($this->once())
                            ->method('setRecipe')
                            ->with($this->identicalTo($databaseRecipe))
                            ->willReturnSelf();
        $databaseIngredient1->expects($this->once())
                            ->method('setOrder')
                            ->with($this->identicalTo(0))
                            ->willReturnSelf();

        $databaseIngredient2 = $this->createMock(DatabaseIngredient::class);
        $databaseIngredient2->expects($this->once())
                            ->method('setRecipe')
                            ->with($this->identicalTo($databaseRecipe))
                            ->willReturnSelf();
        $databaseIngredient2->expects($this->once())
                            ->method('setOrder')
                            ->with($this->identicalTo(1))
                            ->willReturnSelf();

        $ingredientCollection = $this->createMock(Collection::class);
        $ingredientCollection->expects($this->exactly(2))
                             ->method('add')
                             ->withConsecutive(
                                 [$this->identicalTo($databaseIngredient1)],
                                 [$this->identicalTo($databaseIngredient2)]
                             );

        $databaseRecipe->expects($this->any())
                       ->method('getIngredients')
                       ->willReturn($ingredientCollection);

        $instance = $this->createInstance(['mapIngredient']);
        $instance->expects($this->exactly(2))
                 ->method('mapIngredient')
                 ->withConsecutive(
                     [$this->identicalTo($exportIngredient1)],
                     [$this->identicalTo($exportIngredient2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseIngredient1,
                     $databaseIngredient2
                 );

        $this->invokeMethod($instance, 'mapIngredients', $exportRecipe, $databaseRecipe);
    }

    /**
     * @throws ReflectionException
     */
    public function testMapIngredient(): void
    {
        $item = $this->createMock(Item::class);

        $exportIngredient = new ExportIngredient();
        $exportIngredient->type = 'abc';
        $exportIngredient->name = 'def';
        $exportIngredient->amount = 13.37;

        $expectedResult = new DatabaseIngredient();
        $expectedResult->setItem($item)
                       ->setAmount(13.37);

        $this->dataCollector->expects($this->once())
                            ->method('getItem')
                            ->with($this->identicalTo('abc'), $this->identicalTo('def'))
                            ->willReturn($item);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'mapIngredient', $exportIngredient);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testMapProducts(): void
    {
        $exportProduct1 = $this->createMock(ExportProduct::class);
        $exportProduct2 = $this->createMock(ExportProduct::class);

        $exportRecipe = new ExportRecipe();
        $exportRecipe->products = [$exportProduct1, $exportProduct2];

        $databaseRecipe = $this->createMock(DatabaseRecipe::class);

        $databaseProduct1 = $this->createMock(DatabaseProduct::class);
        $databaseProduct1->expects($this->once())
                            ->method('setRecipe')
                            ->with($this->identicalTo($databaseRecipe))
                            ->willReturnSelf();
        $databaseProduct1->expects($this->once())
                            ->method('setOrder')
                            ->with($this->identicalTo(0))
                            ->willReturnSelf();

        $databaseProduct2 = $this->createMock(DatabaseProduct::class);
        $databaseProduct2->expects($this->once())
                            ->method('setRecipe')
                            ->with($this->identicalTo($databaseRecipe))
                            ->willReturnSelf();
        $databaseProduct2->expects($this->once())
                            ->method('setOrder')
                            ->with($this->identicalTo(1))
                            ->willReturnSelf();

        $productCollection = $this->createMock(Collection::class);
        $productCollection->expects($this->exactly(2))
                             ->method('add')
                             ->withConsecutive(
                                 [$this->identicalTo($databaseProduct1)],
                                 [$this->identicalTo($databaseProduct2)]
                             );

        $databaseRecipe->expects($this->any())
                       ->method('getProducts')
                       ->willReturn($productCollection);

        $instance = $this->createInstance(['mapProduct']);
        $instance->expects($this->exactly(2))
                 ->method('mapProduct')
                 ->withConsecutive(
                     [$this->identicalTo($exportProduct1)],
                     [$this->identicalTo($exportProduct2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseProduct1,
                     $databaseProduct2
                 );

        $this->invokeMethod($instance, 'mapProducts', $exportRecipe, $databaseRecipe);
    }

    /**
     * @throws ReflectionException
     */
    public function testMapProduct(): void
    {
        $item = $this->createMock(Item::class);

        $exportProduct = new ExportProduct();
        $exportProduct->type = 'abc';
        $exportProduct->name = 'def';
        $exportProduct->amountMin = 12.34;
        $exportProduct->amountMax = 34.56;
        $exportProduct->probability = 56.78;

        $expectedResult = new DatabaseProduct();
        $expectedResult->setItem($item)
                       ->setAmountMin(12.34)
                       ->setAmountMax(34.56)
                       ->setProbability(56.78);

        $this->dataCollector->expects($this->once())
                            ->method('getItem')
                            ->with($this->identicalTo('abc'), $this->identicalTo('def'))
                            ->willReturn($item);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'mapProduct', $exportProduct);

        $this->assertEquals($expectedResult, $result);
    }
}
