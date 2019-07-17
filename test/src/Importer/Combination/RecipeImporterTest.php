<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Data\RecipeData;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Recipe as DatabaseRecipe;
use FactorioItemBrowser\Api\Database\Entity\RecipeIngredient as DatabaseIngredient;
use FactorioItemBrowser\Api\Database\Entity\RecipeProduct as DatabaseProduct;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Import\Database\CraftingCategoryService;
use FactorioItemBrowser\Api\Import\Database\ItemService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\RecipeImporter;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\Entity\Recipe\Ingredient as ExportIngredient;
use FactorioItemBrowser\ExportData\Entity\Recipe\Product as ExportProduct;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the RecipeImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Combination\RecipeImporter
 */
class RecipeImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ItemService $itemService */
        $itemService = $this->createMock(ItemService::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new RecipeImporter(
            $craftingCategoryService,
            $entityManager,
            $itemService,
            $recipeRepository,
            $registryService
        );

        $this->assertSame($craftingCategoryService, $this->extractProperty($importer, 'craftingCategoryService'));
        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($itemService, $this->extractProperty($importer, 'itemService'));
        $this->assertSame($recipeRepository, $this->extractProperty($importer, 'recipeRepository'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
    }
    
    /**
     * Tests the import method.
     * @throws ImportException
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        $newRecipes = [
            $this->createMock(DatabaseRecipe::class),
            $this->createMock(DatabaseRecipe::class),
        ];
        $existingRecipes = [
            $this->createMock(DatabaseRecipe::class),
            $this->createMock(DatabaseRecipe::class),
        ];
        $persistedRecipes = [
            $this->createMock(DatabaseRecipe::class),
            $this->createMock(DatabaseRecipe::class),
        ];

        /* @var ExportCombination $exportCombination */
        $exportCombination = $this->createMock(ExportCombination::class);
        /* @var Collection $recipeCollection */
        $recipeCollection = $this->createMock(Collection::class);

        /* @var DatabaseCombination|MockObject $databaseCombination */
        $databaseCombination = $this->getMockBuilder(DatabaseCombination::class)
                                    ->setMethods(['getRecipes'])
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $databaseCombination->expects($this->once())
                            ->method('getRecipes')
                            ->willReturn($recipeCollection);

        /* @var RecipeImporter|MockObject $importer */
        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->setMethods([
                             'getRecipesFromCombination',
                             'getExistingRecipes',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getRecipesFromCombination')
                 ->with($exportCombination)
                 ->willReturn($newRecipes);
        $importer->expects($this->once())
                 ->method('getExistingRecipes')
                 ->with($newRecipes)
                 ->willReturn($existingRecipes);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newRecipes, $existingRecipes)
                 ->willReturn($persistedRecipes);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedRecipes, $recipeCollection);

        $importer->import($exportCombination, $databaseCombination);
    }

    /**
     * Tests the getRecipesFromCombination method.
     * @throws ReflectionException
     * @covers ::getRecipesFromCombination
     */
    public function testGetRecipesFromCombination(): void
    {
        /* @var ExportRecipe $exportRecipe1 */
        $exportRecipe1 = $this->createMock(ExportRecipe::class);
        /* @var ExportRecipe $exportRecipe2 */
        $exportRecipe2 = $this->createMock(ExportRecipe::class);
        /* @var ExportRecipe $exportRecipe3 */
        $exportRecipe3 = $this->createMock(ExportRecipe::class);
        /* @var DatabaseRecipe $databaseRecipe1 */
        $databaseRecipe1 = $this->createMock(DatabaseRecipe::class);
        /* @var DatabaseRecipe $databaseRecipe2 */
        $databaseRecipe2 = $this->createMock(DatabaseRecipe::class);

        $recipeHashes = ['abc', 'def', 'ghi'];
        $expectedResult = [
            'jkl' => $databaseRecipe1,
            'mno' => $databaseRecipe2,
        ];

        /* @var ExportCombination|MockObject $exportCombination */
        $exportCombination = $this->getMockBuilder(ExportCombination::class)
                                  ->setMethods(['getRecipeHashes'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportCombination->expects($this->once())
                          ->method('getRecipeHashes')
                          ->willReturn($recipeHashes);

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getRecipe'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(3))
                        ->method('getRecipe')
                        ->withConsecutive(
                            ['abc'],
                            ['def'],
                            ['ghi']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $exportRecipe1,
                            $exportRecipe2,
                            $exportRecipe3
                        );

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ItemService $itemService */
        $itemService = $this->createMock(ItemService::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);

        /* @var RecipeImporter|MockObject $importer */
        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->setMethods(['hasRecipeData', 'mapRecipe', 'getIdentifier'])
                         ->setConstructorArgs([
                             $craftingCategoryService,
                             $entityManager,
                             $itemService,
                             $recipeRepository,
                             $registryService
                         ])
                         ->getMock();
        $importer->expects($this->exactly(3))
                 ->method('hasRecipeData')
                 ->withConsecutive(
                     [$exportRecipe1],
                     [$exportRecipe2],
                     [$exportRecipe3]
                 )
                 ->willReturnOnConsecutiveCalls(
                     true,
                     false,
                     true
                 );
        $importer->expects($this->exactly(2))
                 ->method('mapRecipe')
                 ->withConsecutive(
                     [$exportRecipe1],
                     [$exportRecipe2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseRecipe1,
                     $databaseRecipe2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$databaseRecipe1],
                     [$databaseRecipe2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'jkl',
                     'mno'
                 );

        $result = $this->invokeMethod($importer, 'getRecipesFromCombination', $exportCombination);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Provides the data for the hasRecipeData test.
     * @return array
     */
    public function provideHasRecipeData(): array
    {
        $recipe1 = new ExportRecipe();
        $recipe1->setIngredients([new ExportIngredient(), new ExportIngredient()])
                ->setProducts([]);

        $recipe2 = new ExportRecipe();
        $recipe2->setIngredients([])
                ->setProducts([new ExportProduct(), new ExportProduct()]);

        $recipe3 = new ExportRecipe();
        $recipe3->setIngredients([])
                ->setProducts([]);

        return [
            [$recipe1, true],
            [$recipe2, true],
            [$recipe3, false],
        ];
    }

    /**
     * Tests the hasRecipeData method.
     * @param ExportRecipe $recipe
     * @param bool $expectedResult
     * @throws ReflectionException
     * @covers ::hasRecipeData
     * @dataProvider provideHasRecipeData
     */
    public function testHasRecipeData(ExportRecipe $recipe, bool $expectedResult): void
    {
        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ItemService $itemService */
        $itemService = $this->createMock(ItemService::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new RecipeImporter(
            $craftingCategoryService,
            $entityManager,
            $itemService,
            $recipeRepository,
            $registryService
        );
        $result = $this->invokeMethod($importer, 'hasRecipeData', $recipe);

        $this->assertSame($expectedResult, $result);
    }
    
    /**
     * Tests the mapRecipe method.
     * @throws ReflectionException
     * @covers ::mapRecipe
     */
    public function testMapRecipe(): void
    {
        /* @var CraftingCategory $craftingCategory */
        $craftingCategory = $this->createMock(CraftingCategory::class);
        /* @var DatabaseIngredient $databaseIngredient1 */
        $databaseIngredient1 = $this->createMock(DatabaseIngredient::class);
        /* @var DatabaseIngredient $databaseIngredient2 */
        $databaseIngredient2 = $this->createMock(DatabaseIngredient::class);
        /* @var DatabaseProduct $databaseProduct1 */
        $databaseProduct1 = $this->createMock(DatabaseProduct::class);
        /* @var DatabaseProduct $databaseProduct2 */
        $databaseProduct2 = $this->createMock(DatabaseProduct::class);
        /* @var ExportIngredient $exportIngredient1 */
        $exportIngredient1 = $this->createMock(ExportIngredient::class);
        /* @var ExportIngredient $exportIngredient2 */
        $exportIngredient2 = $this->createMock(ExportIngredient::class);
        /* @var ExportProduct $exportProduct1 */
        $exportProduct1 = $this->createMock(ExportProduct::class);
        /* @var ExportProduct $exportProduct2 */
        $exportProduct2 = $this->createMock(ExportProduct::class);

        $exportRecipe = new ExportRecipe();
        $exportRecipe->setName('abc')
                     ->setMode('def')
                     ->setCraftingCategory('ghi')
                     ->setCraftingTime(13.37)
                     ->setIngredients([$exportIngredient1, $exportIngredient2])
                     ->setProducts([$exportProduct1, $exportProduct2]);

        $expectedResult = new DatabaseRecipe('abc', 'def', $craftingCategory);
        $expectedResult->setCraftingTime(13.37);
        $expectedResult->getIngredients()->add($databaseIngredient1);
        $expectedResult->getIngredients()->add($databaseIngredient2);
        $expectedResult->getProducts()->add($databaseProduct1);
        $expectedResult->getProducts()->add($databaseProduct2);

        /* @var CraftingCategoryService|MockObject $craftingCategoryService */
        $craftingCategoryService = $this->getMockBuilder(CraftingCategoryService::class)
                                        ->setMethods(['getByName'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $craftingCategoryService->expects($this->once())
                                ->method('getByName')
                                ->with('ghi')
                                ->willReturn($craftingCategory);

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ItemService $itemService */
        $itemService = $this->createMock(ItemService::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        /* @var RecipeImporter|MockObject $importer */
        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->setMethods(['mapIngredient', 'mapProduct'])
                         ->setConstructorArgs([
                             $craftingCategoryService,
                             $entityManager,
                             $itemService,
                             $recipeRepository,
                             $registryService,
                         ])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('mapIngredient')
                 ->withConsecutive(
                     [$this->isInstanceOf(DatabaseRecipe::class), $exportIngredient1, 1],
                     [$this->isInstanceOf(DatabaseRecipe::class), $exportIngredient2, 2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseIngredient1,
                     $databaseIngredient2
                 );
        $importer->expects($this->exactly(2))
                 ->method('mapProduct')
                 ->withConsecutive(
                     [$this->isInstanceOf(DatabaseRecipe::class), $exportProduct1, 1],
                     [$this->isInstanceOf(DatabaseRecipe::class), $exportProduct2, 2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseProduct1,
                     $databaseProduct2
                 );

        $result = $this->invokeMethod($importer, 'mapRecipe', $exportRecipe);

        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Tests the mapIngredient method.
     * @throws ReflectionException
     * @covers ::mapIngredient
     */
    public function testMapIngredient(): void
    {
        /* @var Item $item */
        $item = $this->createMock(Item::class);
        /* @var DatabaseRecipe $recipe */
        $recipe = $this->createMock(DatabaseRecipe::class);
        $order = 1337;

        $exportIngredient = new ExportIngredient();
        $exportIngredient->setType('abc')
                         ->setName('def')
                         ->setAmount(4.2);

        $expectedResult = new DatabaseIngredient($recipe, $item);
        $expectedResult->setAmount(4.2)
                       ->setOrder(1337);

        /* @var ItemService|MockObject $itemService */
        $itemService = $this->getMockBuilder(ItemService::class)
                            ->setMethods(['getByTypeAndName'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $itemService->expects($this->once())
                    ->method('getByTypeAndName')
                    ->with('abc', 'def')
                    ->willReturn($item);

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new RecipeImporter(
            $craftingCategoryService,
            $entityManager,
            $itemService,
            $recipeRepository,
            $registryService
        );
        $result = $this->invokeMethod($importer, 'mapIngredient', $recipe, $exportIngredient, $order);

        $this->assertEquals($expectedResult, $result);
    }

    
    /**
     * Tests the mapProduct method.
     * @throws ReflectionException
     * @covers ::mapProduct
     */
    public function testMapProduct(): void
    {
        /* @var Item $item */
        $item = $this->createMock(Item::class);
        /* @var DatabaseRecipe $recipe */
        $recipe = $this->createMock(DatabaseRecipe::class);
        $order = 1337;

        $exportProduct = new ExportProduct();
        $exportProduct->setType('abc')
                      ->setName('def')
                      ->setAmountMin(1.2)
                      ->setAmountMax(2.3)
                      ->setProbability(3.4);

        $expectedResult = new DatabaseProduct($recipe, $item);
        $expectedResult->setAmountMin(1.2)
                       ->setAmountMax(2.3)
                       ->setProbability(3.4)
                       ->setOrder(1337);

        /* @var ItemService|MockObject $itemService */
        $itemService = $this->getMockBuilder(ItemService::class)
                            ->setMethods(['getByTypeAndName'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $itemService->expects($this->once())
                    ->method('getByTypeAndName')
                    ->with('abc', 'def')
                    ->willReturn($item);

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new RecipeImporter(
            $craftingCategoryService,
            $entityManager,
            $itemService,
            $recipeRepository,
            $registryService
        );
        $result = $this->invokeMethod($importer, 'mapProduct', $recipe, $exportProduct, $order);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getExistingRecipes method.
     * @throws ReflectionException
     * @covers ::getExistingRecipes
     */
    public function testGetExistingRecipes(): void
    {
        $recipe1 = new DatabaseRecipe('abc', 'foo', new CraftingCategory('bar'));
        $recipe2 = new DatabaseRecipe('def', 'foo', new CraftingCategory('bar'));
        $expectedNames = ['abc', 'def'];

        $recipeData1 = (new RecipeData())->setId(42);
        $recipeData2 = (new RecipeData())->setId(21);
        $expectedRecipeIds = [42, 21];

        $existingRecipe1 = new DatabaseRecipe('ghi', 'foo', new CraftingCategory('bar'));
        $existingRecipe2 = new DatabaseRecipe('jkl', 'foo', new CraftingCategory('bar'));
        $expectedResult = [
            'mno' => $existingRecipe1,
            'pqr' => $existingRecipe2,
        ];

        /* @var RecipeRepository|MockObject $recipeRepository */
        $recipeRepository = $this->getMockBuilder(RecipeRepository::class)
                                  ->setMethods(['findDataByNames', 'findByIds'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $recipeRepository->expects($this->once())
                          ->method('findDataByNames')
                          ->with($expectedNames)
                          ->willReturn([$recipeData1, $recipeData2]);
        $recipeRepository->expects($this->once())
                          ->method('findByIds')
                          ->with($expectedRecipeIds)
                          ->willReturn([$existingRecipe1, $existingRecipe2]);

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ItemService $itemService */
        $itemService = $this->createMock(ItemService::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        /* @var RecipeImporter|MockObject $importer */
        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->setMethods(['getIdentifier'])
                         ->setConstructorArgs([
                             $craftingCategoryService,
                             $entityManager,
                             $itemService,
                             $recipeRepository,
                             $registryService
                         ])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$existingRecipe1],
                     [$existingRecipe2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'mno',
                     'pqr'
                 );

        $result = $this->invokeMethod($importer, 'getExistingRecipes', [$recipe1, $recipe2]);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getIdentifier method.
     * @throws ReflectionException
     * @covers ::getIdentifier
     */
    public function testGetIdentifier(): void
    {
        $recipe = new DatabaseRecipe('abc', 'def', new CraftingCategory('ghi'));
        $recipe->setCraftingTime(13.37);

        $ingredient1 = new DatabaseIngredient($recipe, new Item('jkl', 'mno'));
        $ingredient1->setAmount(4.2);
        $ingredient2 = new DatabaseIngredient($recipe, new Item('pqr', 'stu'));
        $ingredient2->setAmount(2.1);
        $product1 = new DatabaseProduct($recipe, new Item('vwx', 'yza'));
        $product1->setAmountMin(1.2)
                 ->setAmountMax(2.3)
                 ->setProbability(3.4);
        $product2 = new DatabaseProduct($recipe, new Item('bcd', 'efg'));
        $product2->setAmountMin(4.5)
                 ->setAmountMax(5.6)
                 ->setProbability(6.7);

        $recipe->getIngredients()->add($ingredient1);
        $recipe->getIngredients()->add($ingredient2);
        $recipe->getProducts()->add($product1);
        $recipe->getProducts()->add($product2);

        $expectedResult = EntityUtils::calculateHashOfArray([
            'abc',
            'def',
            13.37,
            'ghi',
            [
                ['jkl', 'mno', 4.2],
                ['pqr', 'stu', 2.1],
            ],
            [
                ['vwx', 'yza', 1.2, 2.3, 3.4],
                ['bcd', 'efg', 4.5, 5.6, 6.7],
            ]
        ]);

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ItemService $itemService */
        $itemService = $this->createMock(ItemService::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new RecipeImporter(
            $craftingCategoryService,
            $entityManager,
            $itemService,
            $recipeRepository,
            $registryService
        );
        $result = $this->invokeMethod($importer, 'getIdentifier', $recipe);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the persistEntity method.
     * @throws ReflectionException
     * @covers ::persistEntity
     */
    public function testPersistEntity(): void
    {
        $recipe = new DatabaseRecipe('abc', 'def', new CraftingCategory('ghi'));

        $ingredient1 = new DatabaseIngredient($recipe, new Item('jkl', 'mno'));
        $ingredient2 = new DatabaseIngredient($recipe, new Item('pqr', 'stu'));
        $product1 = new DatabaseProduct($recipe, new Item('vwx', 'yza'));
        $product2 = new DatabaseProduct($recipe, new Item('bcd', 'efg'));

        $recipe->getIngredients()->add($ingredient1);
        $recipe->getIngredients()->add($ingredient2);
        $recipe->getProducts()->add($product1);
        $recipe->getProducts()->add($product2);

        /* @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManagerInterface::class)
                              ->setMethods(['persist'])
                              ->getMockForAbstractClass();
        $entityManager->expects($this->exactly(5))
                      ->method('persist')
                      ->withConsecutive(
                          [$ingredient1],
                          [$ingredient2],
                          [$product1],
                          [$product2],
                          [$recipe]
                      );

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var ItemService $itemService */
        $itemService = $this->createMock(ItemService::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new RecipeImporter(
            $craftingCategoryService,
            $entityManager,
            $itemService,
            $recipeRepository,
            $registryService
        );
        $this->invokeMethod($importer, 'persistEntity', $recipe);
    }
}
