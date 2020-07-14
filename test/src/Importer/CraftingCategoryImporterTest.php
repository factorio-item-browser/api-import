<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\CraftingCategoryImporter;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
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
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

    /**
     * The mocked repository.
     * @var CraftingCategoryRepository&MockObject
     */
    protected $repository;

    /**
     * The mocked id calculator.
     * @var IdCalculator&MockObject
     */
    protected $idCalculator;

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

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CraftingCategoryRepository::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new CraftingCategoryImporter(
            $this->repository,
            $this->entityManager,
            $this->idCalculator,
            $this->validator,
        );

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
        $emptyCollection = new ArrayCollection();
        $combination = $this->createMock(DatabaseCombination::class);

        $importer = new CraftingCategoryImporter(
            $this->repository,
            $this->entityManager,
            $this->idCalculator,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'getCollectionFromCombination', $combination);

        $this->assertEquals($emptyCollection, $result);
    }

    /**
     * Tests the getExportEntities method.
     * @throws ReflectionException
     * @covers ::getExportEntities
     */
    public function testGetExportEntities(): void
    {
        $recipe1 = new Recipe();
        $recipe1->setCraftingCategory('abc');
        $recipe2 = new Recipe();
        $recipe2->setCraftingCategory('def');
        $recipe3 = new Recipe();
        $recipe3->setCraftingCategory('abc');

        $machine1 = new Machine();
        $machine1->setCraftingCategories(['ghi', 'abc', 'jkl']);
        $machine2 = new Machine();
        $machine2->setCraftingCategories(['jkl']);

        $expectedResult = ['abc', 'def', 'ghi', 'jkl'];

        $combination = new ExportCombination();
        $combination->setRecipes([$recipe1, $recipe2, $recipe3])
                    ->setMachines([$machine1, $machine2]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new CraftingCategoryImporter(
            $this->repository,
            $this->entityManager,
            $this->idCalculator,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals($expectedResult, iterator_to_array($result));
    }

    /**
     * Tests the createDatabaseEntity method.
     * @throws ReflectionException
     * @covers ::createDatabaseEntity
     */
    public function testCreateDatabaseEntity(): void
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

        $this->validator->expects($this->once())
                        ->method('validateCraftingCategory')
                        ->with($this->equalTo($expectedCraftingCategory));

        $importer = new CraftingCategoryImporter(
            $this->repository,
            $this->entityManager,
            $this->idCalculator,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'createDatabaseEntity', $name);

        $this->assertEquals($expectedResult, $result);
    }
}
