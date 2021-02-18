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
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the CraftingCategoryImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\CraftingCategoryImporter
 */
class CraftingCategoryImporterTest extends TestCase
{
    use ReflectionTrait;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var IdCalculator&MockObject */
    private IdCalculator $idCalculator;
    /** @var CraftingCategoryRepository&MockObject */
    private CraftingCategoryRepository $repository;
    /** @var Validator&MockObject */
    private Validator $validator;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->repository = $this->createMock(CraftingCategoryRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return CraftingCategoryImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): CraftingCategoryImporter
    {
        return $this->getMockBuilder(CraftingCategoryImporter::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
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
        $emptyCollection = new ArrayCollection();
        $combination = $this->createMock(DatabaseCombination::class);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getCollectionFromCombination', $combination);

        $this->assertEquals($emptyCollection, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetExportEntities(): void
    {
        $recipe1 = new Recipe();
        $recipe1->craftingCategory = 'abc';
        $recipe2 = new Recipe();
        $recipe2->craftingCategory = 'def';
        $recipe3 = new Recipe();
        $recipe3->craftingCategory = 'abc';

        $machine1 = new Machine();
        $machine1->craftingCategories = ['ghi', 'abc', 'jkl'];
        $machine2 = new Machine();
        $machine2->craftingCategories = ['jkl'];

        $expectedResult = ['abc', 'def', 'ghi', 'jkl'];

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getRecipes()->add($recipe1)
                                 ->add($recipe2)
                                 ->add($recipe3);
        $exportData->getMachines()->add($machine1)
                                  ->add($machine2);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getExportEntities', $exportData);

        $this->assertEquals($expectedResult, iterator_to_array($result));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateDatabaseEntity(): void
    {
        $name = 'abc';
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

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'createDatabaseEntity', $name);

        $this->assertEquals($expectedResult, $result);
    }
}
