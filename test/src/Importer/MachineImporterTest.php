<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Machine as DatabaseMachine;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Import\Helper\DataCollector;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\MachineImporter;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the MachineImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\MachineImporter
 */
class MachineImporterTest extends TestCase
{
    use ReflectionTrait;

    /** @var DataCollector&MockObject */
    private DataCollector $dataCollector;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var IdCalculator&MockObject */
    private IdCalculator $idCalculator;
    /** @var MachineRepository&MockObject */
    private MachineRepository $repository;
    /** @var Validator&MockObject */
    private Validator $validator;

    protected function setUp(): void
    {
        $this->dataCollector = $this->createMock(DataCollector::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->repository = $this->createMock(MachineRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return MachineImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): MachineImporter
    {
        return $this->getMockBuilder(MachineImporter::class)
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
        $machines = $this->createMock(Collection::class);

        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->once())
                    ->method('getMachines')
                    ->willReturn($machines);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getCollectionFromCombination', $combination);

        $this->assertSame($machines, $result);
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
        $machine1 = new ExportMachine();
        $machine1->craftingCategories = ['abc', 'def'];

        $machine2 = new ExportMachine();
        $machine2->craftingCategories = ['ghi'];

        $machine3 = new ExportMachine();
        $machine3->craftingCategories = ['jkl', 'abc'];

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getMachines()->add($machine1)
                                  ->add($machine2)
                                  ->add($machine3);

        $this->dataCollector->expects($this->exactly(5))
                            ->method('addCraftingCategoryName')
                            ->withConsecutive(
                                [$this->identicalTo('abc')],
                                [$this->identicalTo('def')],
                                [$this->identicalTo('ghi')],
                                [$this->identicalTo('jkl')],
                                [$this->identicalTo('abc')],
                            );

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getExportEntities', $exportData);

        $this->assertEquals([$machine1, $machine2, $machine3], iterator_to_array($result));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateDatabaseEntity(): void
    {
        $machineId = $this->createMock(UuidInterface::class);
        $craftingCategory1 = $this->createMock(CraftingCategory::class);
        $craftingCategory2 = $this->createMock(CraftingCategory::class);

        $exportMachine = new ExportMachine();
        $exportMachine->name = 'abc';
        $exportMachine->craftingSpeed = 4.2;
        $exportMachine->numberOfItemSlots = 12;
        $exportMachine->numberOfFluidInputSlots = 34;
        $exportMachine->numberOfFluidOutputSlots = 56;
        $exportMachine->numberOfModuleSlots = 78;
        $exportMachine->energyUsage = 13.37;
        $exportMachine->energyUsageUnit = 'def';
        $exportMachine->craftingCategories = ['ghi', 'jkl'];

        $expectedDatabaseMachine = new DatabaseMachine();
        $expectedDatabaseMachine->setName('abc')
                                ->setCraftingSpeed(4.2)
                                ->setNumberOfItemSlots(12)
                                ->setNumberOfFluidInputSlots(34)
                                ->setNumberOfFluidOutputSlots(56)
                                ->setNumberOfModuleSlots(78)
                                ->setEnergyUsage(13.37)
                                ->setEnergyUsageUnit('def');
        $expectedDatabaseMachine->getCraftingCategories()->add($craftingCategory1);
        $expectedDatabaseMachine->getCraftingCategories()->add($craftingCategory2);

        $expectedResult = new DatabaseMachine();
        $expectedResult->setId($machineId)
                       ->setName('abc')
                       ->setCraftingSpeed(4.2)
                       ->setNumberOfItemSlots(12)
                       ->setNumberOfFluidInputSlots(34)
                       ->setNumberOfFluidOutputSlots(56)
                       ->setNumberOfModuleSlots(78)
                       ->setEnergyUsage(13.37)
                       ->setEnergyUsageUnit('def');
        $expectedResult->getCraftingCategories()->add($craftingCategory1);
        $expectedResult->getCraftingCategories()->add($craftingCategory2);

        $this->dataCollector->expects($this->exactly(2))
                            ->method('getCraftingCategory')
                            ->withConsecutive(
                                [$this->identicalTo('ghi')],
                                [$this->identicalTo('jkl')],
                            )
                            ->willReturnOnConsecutiveCalls(
                                $craftingCategory1,
                                $craftingCategory2,
                            );

        $this->idCalculator->expects($this->once())
                           ->method('calculateIdOfMachine')
                           ->with($this->equalTo($expectedDatabaseMachine))
                           ->willReturn($machineId);

        $this->validator->expects($this->once())
                        ->method('validateMachine')
                        ->with($this->equalTo($expectedDatabaseMachine));

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'createDatabaseEntity', $exportMachine);

        $this->assertEquals($expectedResult, $result);
    }
}
