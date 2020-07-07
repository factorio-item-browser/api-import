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
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Recipe\Ingredient as ExportIngredient;
use FactorioItemBrowser\ExportData\Entity\Recipe\Product as ExportProduct;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the RecipeImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\RecipeImporter
 */
class RecipeImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked data collector.
     * @var DataCollector&MockObject
     */
    protected $dataCollector;

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
     * @var RecipeRepository&MockObject
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

        $this->dataCollector = $this->createMock(DataCollector::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->repository = $this->createMock(RecipeRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new RecipeImporter(
            $this->dataCollector,
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );

        $this->assertSame($this->dataCollector, $this->extractProperty($importer, 'dataCollector'));
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
        $recipes = $this->createMock(Collection::class);

        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->once())
                    ->method('getRecipes')
                    ->willReturn($recipes);

        $importer = new RecipeImporter(
            $this->dataCollector,
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'getCollectionFromCombination', $combination);

        $this->assertSame($recipes, $result);
    }

    /**
     * Tests the prepareImport method.
     * @throws ReflectionException
     * @covers ::prepareImport
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

        $importer = new RecipeImporter(
            $this->dataCollector,
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $this->invokeMethod($importer, 'prepareImport', $combination, $exportData, $offset, $limit);
    }

    /**
     * Tests the getExportEntities method.
     * @throws ReflectionException
     * @covers ::getExportEntities
     */
    public function testGetExportEntities(): void
    {
        $ingredient1 = new ExportIngredient();
        $ingredient1->setType('ghi')
                    ->setName('jkl');
        $ingredient2 = new ExportIngredient();
        $ingredient2->setType('mno')
                    ->setName('pqr');
        $product1 = new ExportProduct();
        $product1->setType('stu')
                 ->setName('vwx');
        $product2 = new ExportProduct();
        $product2->setType('yza')
                 ->setName('bcd');

        $recipe1 = new ExportRecipe();
        $recipe1->setCraftingCategory('abc')
                ->addIngredient($ingredient1)
                ->addIngredient($ingredient2)
                ->addProduct($product1);

        $recipe2 = new ExportRecipe();
        $recipe2->setCraftingCategory('def')
                ->addProduct($product2);

        $recipe3 = new ExportRecipe();
        $recipe3->setCraftingCategory('abc')
                ->addIngredient($ingredient1)
                ->addProduct($product1);

        $combination = new ExportCombination();
        $combination->setRecipes([$recipe1, $recipe2, $recipe3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $this->dataCollector->expects($this->exactly(3))
                            ->method('addCraftingCategory')
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

        $importer = new RecipeImporter(
            $this->dataCollector,
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals([$recipe1, $recipe2, $recipe3], iterator_to_array($result));
    }

    /**
     * Tests the createDatabaseEntity method.
     * @throws ReflectionException
     * @covers ::createDatabaseEntity
     */
    public function testCreateDatabaseEntity(): void
    {
        $recipeId = $this->createMock(UuidInterface::class);
        $craftingCategory = $this->createMock(CraftingCategory::class);

        $exportRecipe = new ExportRecipe();
        $exportRecipe->setName('abc')
                     ->setMode('def')
                     ->setCraftingCategory('ghi')
                     ->setCraftingTime(13.37);

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

        /* @var RecipeImporter&MockObject $importer */
        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->onlyMethods(['mapIngredients', 'mapProducts'])
                         ->setConstructorArgs([
                             $this->dataCollector,
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('mapIngredients')
                 ->with($this->identicalTo($exportRecipe), $this->equalTo($expectedDatabaseRecipe));
        $importer->expects($this->once())
                 ->method('mapProducts')
                 ->with($this->identicalTo($exportRecipe), $this->equalTo($expectedDatabaseRecipe));

        $result = $this->invokeMethod($importer, 'createDatabaseEntity', $exportRecipe);

        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Tests the mapIngredients method.
     * @throws ReflectionException
     * @covers ::mapIngredients
     */
    public function testMapIngredients(): void
    {
        $exportIngredient1 = $this->createMock(ExportIngredient::class);
        $exportIngredient2 = $this->createMock(ExportIngredient::class);

        $exportRecipe = new ExportRecipe();
        $exportRecipe->setIngredients([$exportIngredient1, $exportIngredient2]);

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

        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->onlyMethods(['mapIngredient'])
                         ->setConstructorArgs([
                             $this->dataCollector,
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('mapIngredient')
                 ->withConsecutive(
                     [$this->identicalTo($exportIngredient1)],
                     [$this->identicalTo($exportIngredient2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseIngredient1,
                     $databaseIngredient2
                 );

        $this->invokeMethod($importer, 'mapIngredients', $exportRecipe, $databaseRecipe);
    }

    /**
     * Tests the mapIngredient method.
     * @throws ReflectionException
     * @covers ::mapIngredient
     */
    public function testMapIngredient(): void
    {
        $item = $this->createMock(Item::class);

        $exportIngredient = new ExportIngredient();
        $exportIngredient->setType('abc')
                         ->setName('def')
                         ->setAmount(13.37);

        $expectedResult = new DatabaseIngredient();
        $expectedResult->setItem($item)
                       ->setAmount(13.37);

        $this->dataCollector->expects($this->once())
                            ->method('getItem')
                            ->with($this->identicalTo('abc'), $this->identicalTo('def'))
                            ->willReturn($item);

        $importer = new RecipeImporter(
            $this->dataCollector,
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'mapIngredient', $exportIngredient);

        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Tests the mapProducts method.
     * @throws ReflectionException
     * @covers ::mapProducts
     */
    public function testMapProducts(): void
    {
        $exportProduct1 = $this->createMock(ExportProduct::class);
        $exportProduct2 = $this->createMock(ExportProduct::class);

        $exportRecipe = new ExportRecipe();
        $exportRecipe->setProducts([$exportProduct1, $exportProduct2]);

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

        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->onlyMethods(['mapProduct'])
                         ->setConstructorArgs([
                             $this->dataCollector,
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('mapProduct')
                 ->withConsecutive(
                     [$this->identicalTo($exportProduct1)],
                     [$this->identicalTo($exportProduct2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseProduct1,
                     $databaseProduct2
                 );

        $this->invokeMethod($importer, 'mapProducts', $exportRecipe, $databaseRecipe);
    }
    
    /**
     * Tests the mapProduct method.
     * @throws ReflectionException
     * @covers ::mapProduct
     */
    public function testMapProduct(): void
    {
        $item = $this->createMock(Item::class);

        $exportProduct = new ExportProduct();
        $exportProduct->setType('abc')
                      ->setName('def')
                      ->setAmountMin(12.34)
                      ->setAmountMax(34.56)
                      ->setProbability(56.78);

        $expectedResult = new DatabaseProduct();
        $expectedResult->setItem($item)
                       ->setAmountMin(12.34)
                       ->setAmountMax(34.56)
                       ->setProbability(56.78);

        $this->dataCollector->expects($this->once())
                            ->method('getItem')
                            ->with($this->identicalTo('abc'), $this->identicalTo('def'))
                            ->willReturn($item);

        $importer = new RecipeImporter(
            $this->dataCollector,
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'mapProduct', $exportProduct);

        $this->assertEquals($expectedResult, $result);
    }
}
