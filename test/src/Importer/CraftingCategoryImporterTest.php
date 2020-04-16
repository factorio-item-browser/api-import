<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\MissingCraftingCategoryException;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Importer\CraftingCategoryImporter;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the CraftingCategoryImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\CraftingCategoryImporter
 */
class CraftingCategoryImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked crafting category repository.
     * @var CraftingCategoryRepository&MockObject
     */
    protected $craftingCategoryRepository;

    /**
     * The mocked id calculator.
     * @var IdCalculator&MockObject
     */
    protected $idCalculator;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new CraftingCategoryImporter($this->craftingCategoryRepository, $this->idCalculator);

        $this->assertSame(
            $this->craftingCategoryRepository,
            $this->extractProperty($importer, 'craftingCategoryRepository')
        );
        $this->assertSame($this->idCalculator, $this->extractProperty($importer, 'idCalculator'));
    }

    /**
     * Tests the prepare method.
     * @covers ::prepare
     */
    public function testPrepare(): void
    {
        /* @var UuidInterface&MockObject $id1 */
        $id1 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $id2 */
        $id2 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $id3 */
        $id3 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $id4 */
        $id4 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $id5 */
        $id5 = $this->createMock(UuidInterface::class);

        $craftingCategory1 = new CraftingCategory();
        $craftingCategory1->setId($id1);
        $craftingCategory2 = new CraftingCategory();
        $craftingCategory2->setId($id2);
        $craftingCategory3 = new CraftingCategory();
        $craftingCategory3->setId($id3);
        $craftingCategory4 = new CraftingCategory();
        $craftingCategory4->setId($id4);
        $craftingCategory5 = new CraftingCategory();
        $craftingCategory5->setId($id5);

        $machine1 = new Machine();
        $machine1->setCraftingCategories(['abc', 'def']);
        $machine2 = new Machine();
        $machine2->setCraftingCategories(['ghi']);

        $recipe1 = new Recipe();
        $recipe1->setCraftingCategory('jkl');
        $recipe2 = new Recipe();
        $recipe2->setCraftingCategory('mno');

        $combination = new ExportCombination();
        $combination->setMachines([$machine1, $machine2])
                    ->setRecipes([$recipe1, $recipe2]);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->any())
                   ->method('getCombination')
                   ->willReturn($combination);


        /* @var CraftingCategory&MockObject $existingCraftingCategory1 */
        $existingCraftingCategory1 = $this->createMock(CraftingCategory::class);
        /* @var CraftingCategory&MockObject $existingCraftingCategory2 */
        $existingCraftingCategory2 = $this->createMock(CraftingCategory::class);

        $this->craftingCategoryRepository->expects($this->once())
                                         ->method('findByIds')
                                         ->with($this->identicalTo([$id1, $id2, $id3, $id4, $id5]))
                                         ->willReturn([$existingCraftingCategory1, $existingCraftingCategory2]);

        /* @var CraftingCategoryImporter&MockObject $importer */
        $importer = $this->getMockBuilder(CraftingCategoryImporter::class)
                         ->onlyMethods(['create', 'add'])
                         ->setConstructorArgs([$this->craftingCategoryRepository, $this->idCalculator])
                         ->getMock();
        $importer->expects($this->exactly(5))
                 ->method('create')
                 ->withConsecutive(
                     [$this->identicalTo('abc')],
                     [$this->identicalTo('def')],
                     [$this->identicalTo('ghi')],
                     [$this->identicalTo('jkl')],
                     [$this->identicalTo('mno')]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $craftingCategory1,
                     $craftingCategory2,
                     $craftingCategory3,
                     $craftingCategory4,
                     $craftingCategory5
                 );
        $importer->expects($this->exactly(7))
                 ->method('add')
                 ->withConsecutive(
                     [$this->identicalTo($craftingCategory1)],
                     [$this->identicalTo($craftingCategory2)],
                     [$this->identicalTo($craftingCategory3)],
                     [$this->identicalTo($craftingCategory4)],
                     [$this->identicalTo($craftingCategory5)],
                     [$this->identicalTo($existingCraftingCategory1)],
                     [$this->identicalTo($existingCraftingCategory2)]
                 );

        $importer->prepare($exportData);
    }

    /**
     * Tests the create method.
     * @throws ReflectionException
     * @covers ::create
     */
    public function testCreate(): void
    {
        $name = 'abc';
        
        /* @var UuidInterface&MockObject $id */
        $id = $this->createMock(UuidInterface::class);
        
        $expectedCraftingCategory = new CraftingCategory();
        $expectedCraftingCategory->setName('abc');
        
        $expectedResult = new CraftingCategory();
        $expectedResult->setId($id)
                       ->setName('abc');

        $this->idCalculator->expects($this->once())
                           ->method('calculateIdOfCraftingCategory')
                           ->with($this->equalTo($expectedCraftingCategory))
                           ->willReturn($id);

        $importer = new CraftingCategoryImporter($this->craftingCategoryRepository, $this->idCalculator);
        $result = $this->invokeMethod($importer, 'create', $name);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the add method.
     * @throws ReflectionException
     * @covers ::add
     */
    public function testAdd(): void
    {
        $craftingCategory = new CraftingCategory();
        $craftingCategory->setName('abc');

        $expectedCraftingCategories = [
            'abc' => $craftingCategory,
        ];

        $importer = new CraftingCategoryImporter($this->craftingCategoryRepository, $this->idCalculator);
        $this->invokeMethod($importer, 'add', $craftingCategory);

        $this->assertSame($expectedCraftingCategories, $this->extractProperty($importer, 'craftingCategories'));
    }

    /**
     * Tests the getByName method.
     * @throws ImportException
     * @throws ReflectionException
     * @covers ::getByName
     */
    public function testGetByName(): void
    {
        $name = 'abc';

        /* @var CraftingCategory&MockObject $craftingCategory */
        $craftingCategory = $this->createMock(CraftingCategory::class);

        $craftingCategories = [
            'abc' => $craftingCategory,
        ];

        $importer = new CraftingCategoryImporter($this->craftingCategoryRepository, $this->idCalculator);
        $this->injectProperty($importer, 'craftingCategories', $craftingCategories);

        $result = $importer->getByName($name);

        $this->assertSame($craftingCategory, $result);
    }

    /**
     * Tests the getByName method.
     * @throws ImportException
     * @covers ::getByName
     */
    public function testGetByNameWithoutMatch(): void
    {
        $name = 'abc';

        $this->expectException(MissingCraftingCategoryException::class);

        $importer = new CraftingCategoryImporter($this->craftingCategoryRepository, $this->idCalculator);
        $importer->getByName($name);
    }

    /**
     * Tests the parse method.
     * @covers ::parse
     */
    public function testParse(): void
    {
        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);

        $importer = new CraftingCategoryImporter($this->craftingCategoryRepository, $this->idCalculator);
        $importer->parse($exportData);

        $this->addToAssertionCount(1);
    }

    /**
     * Tests the persist method.
     * @throws ReflectionException
     * @covers ::persist
     */
    public function testPersist(): void
    {
        /* @var CraftingCategory&MockObject $craftingCategory1 */
        $craftingCategory1 = $this->createMock(CraftingCategory::class);
        /* @var CraftingCategory&MockObject $craftingCategory2 */
        $craftingCategory2 = $this->createMock(CraftingCategory::class);

        $craftingCategories = [$craftingCategory1, $craftingCategory2];

        /* @var DatabaseCombination&MockObject $combination */
        $combination = $this->createMock(DatabaseCombination::class);

        /* @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
                      ->method('persist')
                      ->withConsecutive(
                          [$this->identicalTo($craftingCategory1)],
                          [$this->identicalTo($craftingCategory2)]
                      );

        $importer = new CraftingCategoryImporter($this->craftingCategoryRepository, $this->idCalculator);
        $this->injectProperty($importer, 'craftingCategories', $craftingCategories);

        $importer->persist($entityManager, $combination);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $this->craftingCategoryRepository->expects($this->once())
                                         ->method('removeOrphans');

        $importer = new CraftingCategoryImporter($this->craftingCategoryRepository, $this->idCalculator);
        $importer->cleanup();
    }
}
