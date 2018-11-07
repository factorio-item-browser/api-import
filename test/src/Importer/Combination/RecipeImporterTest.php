<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
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
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
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
        /* @var DatabaseRecipe $databaseRecipe1 */
        $databaseRecipe1 = $this->createMock(DatabaseRecipe::class);
        /* @var DatabaseRecipe $databaseRecipe2 */
        $databaseRecipe2 = $this->createMock(DatabaseRecipe::class);

        $recipeHashes = ['abc', 'def'];
        $expectedResult = [
            'ghi' => $databaseRecipe1,
            'jkl' => $databaseRecipe2,
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
        $registryService->expects($this->exactly(2))
                        ->method('getRecipe')
                        ->withConsecutive(
                            ['abc'],
                            ['def']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $exportRecipe1,
                            $exportRecipe2
                        );

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ItemService $itemService */
        $itemService = $this->createMock(ItemService::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);

        /* @var RecipeImporter|MockObject $importer */
        $importer = $this->getMockBuilder(RecipeImporter::class)
                         ->setMethods(['mapRecipe', 'getIdentifier'])
                         ->setConstructorArgs([
                             $craftingCategoryService,
                             $entityManager,
                             $itemService,
                             $recipeRepository,
                             $registryService
                         ])
                         ->getMock();
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
                     'ghi',
                     'jkl'
                 );

        $result = $this->invokeMethod($importer, 'getRecipesFromCombination', $exportCombination);
        $this->assertEquals($expectedResult, $result);
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

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
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
                         ->setAmount(42);

        $expectedResult = new DatabaseIngredient($recipe, $item);
        $expectedResult->setAmount(42)
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
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
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
                      ->setAmountMin(12)
                      ->setAmountMax(23)
                      ->setProbability(4.2);

        $expectedResult = new DatabaseProduct($recipe, $item);
        $expectedResult->setAmountMin(12)
                       ->setAmountMax(23)
                       ->setProbability(4.2)
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
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
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
}
