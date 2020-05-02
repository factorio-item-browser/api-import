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
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\CraftingCategoryImporter;
use FactorioItemBrowser\Api\Import\Importer\ItemImporter;
use FactorioItemBrowser\Api\Import\Importer\RecipeImporter;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Recipe\Ingredient as ExportIngredient;
use FactorioItemBrowser\ExportData\Entity\Recipe\Product as ExportProduct;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\ExportData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
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
     * The mocked crafting category importer.
     * @var CraftingCategoryImporter&MockObject
     */
    protected $craftingCategoryImporter;

    /**
     * The mocked id calculator.
     * @var IdCalculator&MockObject
     */
    protected $idCalculator;

    /**
     * The mocked item importer.
     * @var ItemImporter&MockObject
     */
    protected $itemImporter;

    /**
     * The mocked recipe repository.
     * @var RecipeRepository&MockObject
     */
    protected $recipeRepository;

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

        $this->craftingCategoryImporter = $this->createMock(CraftingCategoryImporter::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->itemImporter = $this->createMock(ItemImporter::class);
        $this->recipeRepository = $this->createMock(RecipeRepository::class);
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
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->itemImporter,
            $this->recipeRepository,
            $this->validator
        );

        $this->assertSame(
            $this->craftingCategoryImporter,
            $this->extractProperty($importer, 'craftingCategoryImporter')
        );
        $this->assertSame($this->idCalculator, $this->extractProperty($importer, 'idCalculator'));
        $this->assertSame($this->itemImporter, $this->extractProperty($importer, 'itemImporter'));
        $this->assertSame($this->recipeRepository, $this->extractProperty($importer, 'recipeRepository'));
        $this->assertSame($this->validator, $this->extractProperty($importer, 'validator'));
    }

    /**
     * Tests the prepare method.
     * @throws ReflectionException
     * @covers ::prepare
     */
    public function testPrepare(): void
    {
        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);

        $importer = new RecipeImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->itemImporter,
            $this->recipeRepository,
            $this->validator
        );
        $importer->prepare($exportData);

        $this->assertSame([], $this->extractProperty($importer, 'recipes'));
    }

    /**
     * Tests the parse method.
     * @throws ImportException
     * @covers ::parse
     */
    public function testParse(): void
    {
        /* @var UuidInterface&MockObject $recipeId1 */
        $recipeId1 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $recipeId2 */
        $recipeId2 = $this->createMock(UuidInterface::class);

        /* @var ExportRecipe&MockObject $exportRecipe1 */
        $exportRecipe1 = $this->createMock(ExportRecipe::class);
        /* @var ExportRecipe&MockObject $exportRecipe2 */
        $exportRecipe2 = $this->createMock(ExportRecipe::class);

        $combination = new ExportCombination();
        $combination->setRecipes([$exportRecipe1, $exportRecipe2]);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->once())
                   ->method('getCombination')
                   ->willReturn($combination);

        /* @var DatabaseRecipe&MockObject $databaseRecipe1 */
        $databaseRecipe1 = $this->createMock(DatabaseRecipe::class);
        $databaseRecipe1->expects($this->any())
                      ->method('getId')
                      ->willReturn($recipeId1);

        /* @var DatabaseRecipe&MockObject $databaseRecipe2 */
        $databaseRecipe2 = $this->createMock(DatabaseRecipe::class);
        $databaseRecipe2->expects($this->any())
                         ->method('getId')
                         ->willReturn($recipeId2);

        /* @var DatabaseRecipe&MockObject $existingDatabaseRecipe1 */
        $existingDatabaseRecipe1 = $this->createMock(DatabaseRecipe::class);
        /* @var DatabaseRecipe&MockObject $existingDatabaseRecipe2 */
        $existingDatabaseRecipe2 = $this->createMock(DatabaseRecipe::class);

        $this->recipeRepository->expects($this->once())
                                ->method('findByIds')
                                ->with($this->identicalTo([$recipeId1, $recipeId2]))
                                ->willReturn([$existingDatabaseRecipe1, $existingDatabaseRecipe2]);

        /* @var RecipeImporter&MockObject $importer */
        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->onlyMethods(['mapRecipe', 'add'])
                         ->setConstructorArgs([
                             $this->craftingCategoryImporter,
                             $this->idCalculator,
                             $this->itemImporter,
                             $this->recipeRepository,
                             $this->validator,
                         ])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('mapRecipe')
                 ->withConsecutive(
                     [$this->identicalTo($exportRecipe1)],
                     [$this->identicalTo($exportRecipe2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseRecipe1,
                     $databaseRecipe2
                 );
        $importer->expects($this->exactly(4))
                 ->method('add')
                 ->withConsecutive(
                     [$databaseRecipe1],
                     [$databaseRecipe2],
                     [$existingDatabaseRecipe1],
                     [$existingDatabaseRecipe2]
                 );

        $importer->parse($exportData);
    }

    /**
     * Tests the mapRecipe method.
     * @throws ReflectionException
     * @covers ::mapRecipe
     */
    public function testMapRecipe(): void
    {
        /* @var UuidInterface&MockObject $recipeId */
        $recipeId = $this->createMock(UuidInterface::class);
        /* @var CraftingCategory&MockObject $craftingCategory */
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

        $this->craftingCategoryImporter->expects($this->once())
                                       ->method('getByName')
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
                             $this->craftingCategoryImporter,
                             $this->idCalculator,
                             $this->itemImporter,
                             $this->recipeRepository,
                             $this->validator,
                         ])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('mapIngredients')
                 ->with($this->identicalTo($exportRecipe), $this->equalTo($expectedDatabaseRecipe));
        $importer->expects($this->once())
                 ->method('mapProducts')
                 ->with($this->identicalTo($exportRecipe), $this->equalTo($expectedDatabaseRecipe));

        $result = $this->invokeMethod($importer, 'mapRecipe', $exportRecipe);

        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Tests the mapIngredients method.
     * @throws ReflectionException
     * @covers ::mapIngredients
     */
    public function testMapIngredients(): void
    {
        /* @var ExportIngredient&MockObject $exportIngredient1 */
        $exportIngredient1 = $this->createMock(ExportIngredient::class);
        /* @var ExportIngredient&MockObject $exportIngredient2 */
        $exportIngredient2 = $this->createMock(ExportIngredient::class);

        $exportRecipe = new ExportRecipe();
        $exportRecipe->setIngredients([$exportIngredient1, $exportIngredient2]);

        /* @var DatabaseRecipe&MockObject $databaseRecipe */
        $databaseRecipe = $this->createMock(DatabaseRecipe::class);

        /* @var DatabaseIngredient&MockObject $databaseIngredient1 */
        $databaseIngredient1 = $this->createMock(DatabaseIngredient::class);
        $databaseIngredient1->expects($this->once())
                            ->method('setRecipe')
                            ->with($this->identicalTo($databaseRecipe))
                            ->willReturnSelf();
        $databaseIngredient1->expects($this->once())
                            ->method('setOrder')
                            ->with($this->identicalTo(0))
                            ->willReturnSelf();

        /* @var DatabaseIngredient&MockObject $databaseIngredient2 */
        $databaseIngredient2 = $this->createMock(DatabaseIngredient::class);
        $databaseIngredient2->expects($this->once())
                            ->method('setRecipe')
                            ->with($this->identicalTo($databaseRecipe))
                            ->willReturnSelf();
        $databaseIngredient2->expects($this->once())
                            ->method('setOrder')
                            ->with($this->identicalTo(1))
                            ->willReturnSelf();

        /* @var Collection&MockObject $ingredientCollection */
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

        /* @var RecipeImporter&MockObject $importer */
        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->onlyMethods(['mapIngredient'])
                         ->setConstructorArgs([
                             $this->craftingCategoryImporter,
                             $this->idCalculator,
                             $this->itemImporter,
                             $this->recipeRepository,
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
        /* @var Item&MockObject $item */
        $item = $this->createMock(Item::class);

        $exportIngredient = new ExportIngredient();
        $exportIngredient->setType('abc')
                         ->setName('def')
                         ->setAmount(13.37);

        $expectedResult = new DatabaseIngredient();
        $expectedResult->setItem($item)
                       ->setAmount(13.37);

        $this->itemImporter->expects($this->once())
                           ->method('getByTypeAndName')
                           ->with($this->identicalTo('abc'), $this->identicalTo('def'))
                           ->willReturn($item);
        
        $importer = new RecipeImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->itemImporter,
            $this->recipeRepository,
            $this->validator
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
        /* @var ExportProduct&MockObject $exportProduct1 */
        $exportProduct1 = $this->createMock(ExportProduct::class);
        /* @var ExportProduct&MockObject $exportProduct2 */
        $exportProduct2 = $this->createMock(ExportProduct::class);

        $exportRecipe = new ExportRecipe();
        $exportRecipe->setProducts([$exportProduct1, $exportProduct2]);

        /* @var DatabaseRecipe&MockObject $databaseRecipe */
        $databaseRecipe = $this->createMock(DatabaseRecipe::class);

        /* @var DatabaseProduct&MockObject $databaseProduct1 */
        $databaseProduct1 = $this->createMock(DatabaseProduct::class);
        $databaseProduct1->expects($this->once())
                            ->method('setRecipe')
                            ->with($this->identicalTo($databaseRecipe))
                            ->willReturnSelf();
        $databaseProduct1->expects($this->once())
                            ->method('setOrder')
                            ->with($this->identicalTo(0))
                            ->willReturnSelf();

        /* @var DatabaseProduct&MockObject $databaseProduct2 */
        $databaseProduct2 = $this->createMock(DatabaseProduct::class);
        $databaseProduct2->expects($this->once())
                            ->method('setRecipe')
                            ->with($this->identicalTo($databaseRecipe))
                            ->willReturnSelf();
        $databaseProduct2->expects($this->once())
                            ->method('setOrder')
                            ->with($this->identicalTo(1))
                            ->willReturnSelf();

        /* @var Collection&MockObject $productCollection */
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

        /* @var RecipeImporter&MockObject $importer */
        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->onlyMethods(['mapProduct'])
                         ->setConstructorArgs([
                             $this->craftingCategoryImporter,
                             $this->idCalculator,
                             $this->itemImporter,
                             $this->recipeRepository,
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
        /* @var Item&MockObject $item */
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

        $this->itemImporter->expects($this->once())
                           ->method('getByTypeAndName')
                           ->with($this->identicalTo('abc'), $this->identicalTo('def'))
                           ->willReturn($item);
        
        $importer = new RecipeImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->itemImporter,
            $this->recipeRepository,
            $this->validator
        );
        $result = $this->invokeMethod($importer, 'mapProduct', $exportProduct);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the add method.
     * @throws ReflectionException
     * @covers ::add
     */
    public function testAdd(): void
    {
        $recipeId = Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        $recipe = new DatabaseRecipe();
        $recipe->setId($recipeId);

        $expectedRecipes = [
            '70acdb0f-36ca-4b30-9687-2baaade94cd3' => $recipe,
        ];

        $importer = new RecipeImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->itemImporter,
            $this->recipeRepository,
            $this->validator
        );
        $this->invokeMethod($importer, 'add', $recipe);

        $this->assertSame($expectedRecipes, $this->extractProperty($importer, 'recipes'));
    }
    
    /**
     * Tests the persist method.
     * @throws ReflectionException
     * @covers ::persist
     */
    public function testPersist(): void
    {
        /* @var DatabaseRecipe&MockObject $recipe1 */
        $recipe1 = $this->createMock(DatabaseRecipe::class);
        /* @var DatabaseRecipe&MockObject $recipe2 */
        $recipe2 = $this->createMock(DatabaseRecipe::class);
        $recipes = [$recipe1, $recipe2];

        /* @var Collection&MockObject $recipeCollection */
        $recipeCollection = $this->createMock(Collection::class);
        $recipeCollection->expects($this->once())
                          ->method('clear');
        $recipeCollection->expects($this->exactly(2))
                          ->method('add')
                          ->withConsecutive(
                              [$this->identicalTo($recipe1)],
                              [$this->identicalTo($recipe2)]
                          );

        /* @var DatabaseCombination&MockObject $combination */
        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->any())
                    ->method('getRecipes')
                    ->willReturn($recipeCollection);

        /* @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
                      ->method('persist')
                      ->withConsecutive(
                          [$this->identicalTo($recipe1)],
                          [$this->identicalTo($recipe2)]
                      );

        $importer = new RecipeImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->itemImporter,
            $this->recipeRepository,
            $this->validator
        );
        $this->injectProperty($importer, 'recipes', $recipes);

        $importer->persist($entityManager, $combination);
    }


    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $this->recipeRepository->expects($this->once())
                                ->method('removeOrphans');

        $this->craftingCategoryImporter->expects($this->once())
                                       ->method('cleanup');

        $this->itemImporter->expects($this->once())
                           ->method('cleanup');

        $importer = new RecipeImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->itemImporter,
            $this->recipeRepository,
            $this->validator
        );
        $importer->cleanup();
    }
}
