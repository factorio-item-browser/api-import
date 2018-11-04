<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\CraftingCategoryImporter;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the CraftingCategoryImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Combination\CraftingCategoryImporter
 */
class CraftingCategoryImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new CraftingCategoryImporter($craftingCategoryRepository, $entityManager, $registryService);

        $this->assertSame($craftingCategoryRepository, $this->extractProperty($importer, 'craftingCategoryRepository'));
        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        $category1 = new CraftingCategory('abc');
        $category2 = new CraftingCategory('def');
        $category3 = new CraftingCategory('ghi');
        $category4 = new CraftingCategory('jkl');

        $machineHashes = ['mno', 'pqr'];
        $recipeHashes = ['stu', 'vwx'];
        $exportCombination = new ExportCombination();
        $exportCombination->setMachineHashes($machineHashes)
                          ->setRecipeHashes($recipeHashes);

        /* @var DatabaseCombination $databaseCombination */
        $databaseCombination = $this->createMock(DatabaseCombination::class);

        /* @var CraftingCategoryImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CraftingCategoryImporter::class)
                         ->setMethods([
                             'getCraftingCategoriesFromMachineHashes',
                             'getCraftingCategoriesFromRecipeHashes',
                             'getExistingCraftingCategories',
                             'persistEntities',
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getCraftingCategoriesFromMachineHashes')
                 ->with($machineHashes)
                 ->willReturn(['abc' => $category1, 'def' => $category2]);
        $importer->expects($this->once())
                 ->method('getCraftingCategoriesFromRecipeHashes')
                 ->with($recipeHashes)
                 ->willReturn(['abc' => $category1, 'ghi' => $category3]);
        $importer->expects($this->once())
                 ->method('getExistingCraftingCategories')
                 ->with(['abc' => $category1, 'def' => $category2, 'ghi' => $category3])
                 ->willReturn(['jkl' => $category4]);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with(['abc' => $category1, 'def' => $category2, 'ghi' => $category3], ['jkl' => $category4]);

        $importer->import($exportCombination, $databaseCombination);
    }

    /**
     * Tests the getCraftingCategoriesFromMachineHashes method.
     * @throws ReflectionException
     * @covers ::getCraftingCategoriesFromMachineHashes
     */
    public function testGetCraftingCategoriesFromMachineHashes(): void
    {
        $machineHashes = ['abc', 'def'];
        $machine1 = (new Machine())->setCraftingCategories(['ghi', 'jkl']);
        $machine2 = (new Machine())->setCraftingCategories(['mno']);
        $category1 = new CraftingCategory('ghi');
        $category2 = new CraftingCategory('jkl');
        $category3 = new CraftingCategory('mno');
        $expectedResult = ['ghi' => $category1, 'jkl' => $category2, 'mno' => $category3];

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getMachine'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(2))
                        ->method('getMachine')
                        ->withConsecutive(
                            ['abc'],
                            ['def']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $machine1,
                            $machine2
                        );

        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        /* @var CraftingCategoryImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CraftingCategoryImporter::class)
                         ->setMethods(['createCraftingCategory', 'getIdentifier'])
                         ->setConstructorArgs([$craftingCategoryRepository, $entityManager, $registryService])
                         ->getMock();
        $importer->expects($this->exactly(3))
                 ->method('createCraftingCategory')
                 ->withConsecutive(
                     ['ghi'],
                     ['jkl'],
                     ['mno']
                 )
                 ->willReturnOnConsecutiveCalls(
                     $category1,
                     $category2,
                     $category3
                 );
        $importer->expects($this->exactly(3))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$category1],
                     [$category2],
                     [$category3]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'ghi',
                     'jkl',
                     'mno'
                 );

        $result = $this->invokeMethod($importer, 'getCraftingCategoriesFromMachineHashes', $machineHashes);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getCraftingCategoriesFromRecipeHashes method.
     * @throws ReflectionException
     * @covers ::getCraftingCategoriesFromRecipeHashes
     */
    public function testGetCraftingCategoriesFromRecipeHashes(): void
    {
        $recipeHashes = ['abc', 'def'];
        $recipe1 = (new Recipe())->setCraftingCategory('ghi');
        $recipe2 = (new Recipe())->setCraftingCategory('jkl');
        $category1 = new CraftingCategory('ghi');
        $category2 = new CraftingCategory('jkl');
        $expectedResult = ['ghi' => $category1, 'jkl' => $category2];

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
                            $recipe1,
                            $recipe2
                        );

        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        /* @var CraftingCategoryImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CraftingCategoryImporter::class)
                         ->setMethods(['createCraftingCategory', 'getIdentifier'])
                         ->setConstructorArgs([$craftingCategoryRepository, $entityManager, $registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('createCraftingCategory')
                 ->withConsecutive(
                     ['ghi'],
                     ['jkl']
                 )
                 ->willReturnOnConsecutiveCalls(
                     $category1,
                     $category2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$category1],
                     [$category2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'ghi',
                     'jkl'
                 );

        $result = $this->invokeMethod($importer, 'getCraftingCategoriesFromRecipeHashes', $recipeHashes);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the createCraftingCategory method.
     * @throws ReflectionException
     * @covers ::createCraftingCategory
     */
    public function testCreateCraftingCategory(): void
    {
        $name = 'abc';
        $expectedResult = new CraftingCategory('abc');

        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new CraftingCategoryImporter($craftingCategoryRepository, $entityManager, $registryService);

        $result = $this->invokeMethod($importer, 'createCraftingCategory', $name);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getExistingCraftingCategories method.
     * @throws ReflectionException
     * @covers ::getExistingCraftingCategories
     */
    public function testGetExistingCraftingCategories(): void
    {
        $category1 = new CraftingCategory('abc');
        $category2 = new CraftingCategory('def');
        $category3 = new CraftingCategory('ghi');
        $category4 = new CraftingCategory('jkl');

        $names = ['abc', 'def'];
        $categories = ['abc' => $category1, 'def' => $category2];
        $expectedResult = ['ghi' => $category3, 'jkl' => $category4];

        /* @var CraftingCategoryRepository|MockObject $craftingCategoryRepository */
        $craftingCategoryRepository = $this->getMockBuilder(CraftingCategoryRepository::class)
                                           ->setMethods(['findByNames'])
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $craftingCategoryRepository->expects($this->once())
                                   ->method('findByNames')
                                   ->with($names)
                                   ->willReturn([$category3, $category4]);

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        /* @var CraftingCategoryImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CraftingCategoryImporter::class)
                         ->setMethods(['getIdentifier'])
                         ->setConstructorArgs([$craftingCategoryRepository, $entityManager, $registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$category3],
                     [$category4]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'ghi',
                     'jkl'
                 );

        $result = $this->invokeMethod($importer, 'getExistingCraftingCategories', $categories);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getIdentifier method.
     * @throws ReflectionException
     * @covers ::getIdentifier
     */
    public function testGetIdentifier(): void
    {
        $craftingCategory = new CraftingCategory('abc');
        $expectedResult = 'abc';

        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new CraftingCategoryImporter($craftingCategoryRepository, $entityManager, $registryService);

        $result = $this->invokeMethod($importer, 'getIdentifier', $craftingCategory);

        $this->assertSame($expectedResult, $result);
    }
}
