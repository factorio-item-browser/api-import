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
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the MachineImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\MachineImporter
 */
class MachineImporterTest extends TestCase
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
     * @var MachineRepository&MockObject
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
        $this->repository = $this->createMock(MachineRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new MachineImporter(
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
        $machines = $this->createMock(Collection::class);

        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->once())
                    ->method('getMachines')
                    ->willReturn($machines);

        $importer = new MachineImporter(
            $this->dataCollector,
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'getCollectionFromCombination', $combination);

        $this->assertSame($machines, $result);
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

        $importer = new MachineImporter(
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
        $machine1 = new ExportMachine();
        $machine1->setCraftingCategories(['abc', 'def']);

        $machine2 = new ExportMachine();
        $machine2->setCraftingCategories(['ghi']);

        $machine3 = new ExportMachine();
        $machine3->setCraftingCategories(['jkl', 'abc']);

        $combination = new ExportCombination();
        $combination->setMachines([$machine1, $machine2, $machine3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $this->dataCollector->expects($this->exactly(5))
                            ->method('addCraftingCategory')
                            ->withConsecutive(
                                [$this->identicalTo('abc')],
                                [$this->identicalTo('def')],
                                [$this->identicalTo('ghi')],
                                [$this->identicalTo('jkl')],
                                [$this->identicalTo('abc')],
                            );
        
        $importer = new MachineImporter(
            $this->dataCollector,
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals([$machine1, $machine2, $machine3], iterator_to_array($result));
    }
    
    /**
     * Tests the map method.
     * @throws ReflectionException
     * @covers ::createDatabaseEntity
     */
    public function testCreateDatabaseEntity(): void
    {
        /* @var UuidInterface&MockObject $machineId */
        $machineId = $this->createMock(UuidInterface::class);

        /* @var CraftingCategory&MockObject $craftingCategory1 */
        $craftingCategory1 = $this->createMock(CraftingCategory::class);
        /* @var CraftingCategory&MockObject $craftingCategory2 */
        $craftingCategory2 = $this->createMock(CraftingCategory::class);

        $exportMachine = new ExportMachine();
        $exportMachine->setName('abc')
                      ->setCraftingSpeed(4.2)
                      ->setNumberOfItemSlots(12)
                      ->setNumberOfFluidInputSlots(34)
                      ->setNumberOfFluidOutputSlots(56)
                      ->setNumberOfModuleSlots(78)
                      ->setEnergyUsage(13.37)
                      ->setEnergyUsageUnit('def')
                      ->setCraftingCategories(['ghi', 'jkl']);

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

        $importer = new MachineImporter(
            $this->dataCollector, 
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator
        );
        $result = $this->invokeMethod($importer, 'createDatabaseEntity', $exportMachine);

        $this->assertEquals($expectedResult, $result);
    }
}
