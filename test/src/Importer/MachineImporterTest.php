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
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\CraftingCategoryImporter;
use FactorioItemBrowser\Api\Import\Importer\MachineImporter;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\ExportData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
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
     * The mocked machine repository.
     * @var MachineRepository&MockObject
     */
    protected $machineRepository;

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
        $this->machineRepository = $this->createMock(MachineRepository::class);
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
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->machineRepository,
            $this->validator
        );

        $this->assertSame(
            $this->craftingCategoryImporter,
            $this->extractProperty($importer, 'craftingCategoryImporter')
        );
        $this->assertSame($this->idCalculator, $this->extractProperty($importer, 'idCalculator'));
        $this->assertSame($this->machineRepository, $this->extractProperty($importer, 'machineRepository'));
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

        $importer = new MachineImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->machineRepository,
            $this->validator
        );
        $importer->prepare($exportData);

        $this->assertSame([], $this->extractProperty($importer, 'machines'));
    }
    
    /**
     * Tests the parse method.
     * @throws ImportException
     * @covers ::parse
     */
    public function testParse(): void
    {
        /* @var UuidInterface&MockObject $machineId1 */
        $machineId1 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $machineId2 */
        $machineId2 = $this->createMock(UuidInterface::class);

        /* @var ExportMachine&MockObject $exportMachine1 */
        $exportMachine1 = $this->createMock(ExportMachine::class);
        /* @var ExportMachine&MockObject $exportMachine2 */
        $exportMachine2 = $this->createMock(ExportMachine::class);

        $combination = new ExportCombination();
        $combination->setMachines([$exportMachine1, $exportMachine2]);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->once())
                   ->method('getCombination')
                   ->willReturn($combination);

        /* @var DatabaseMachine&MockObject $databaseMachine1 */
        $databaseMachine1 = $this->createMock(DatabaseMachine::class);
        $databaseMachine1->expects($this->any())
                      ->method('getId')
                      ->willReturn($machineId1);

        /* @var DatabaseMachine&MockObject $databaseMachine2 */
        $databaseMachine2 = $this->createMock(DatabaseMachine::class);
        $databaseMachine2->expects($this->any())
                         ->method('getId')
                         ->willReturn($machineId2);

        /* @var DatabaseMachine&MockObject $existingDatabaseMachine1 */
        $existingDatabaseMachine1 = $this->createMock(DatabaseMachine::class);
        /* @var DatabaseMachine&MockObject $existingDatabaseMachine2 */
        $existingDatabaseMachine2 = $this->createMock(DatabaseMachine::class);

        $this->machineRepository->expects($this->once())
                                ->method('findByIds')
                                ->with($this->identicalTo([$machineId1, $machineId2]))
                                ->willReturn([$existingDatabaseMachine1, $existingDatabaseMachine2]);

        /* @var MachineImporter&MockObject $importer */
        $importer = $this->getMockBuilder(MachineImporter::class)
                         ->onlyMethods(['map', 'add'])
                         ->setConstructorArgs([
                             $this->craftingCategoryImporter,
                             $this->idCalculator,
                             $this->machineRepository,
                             $this->validator,
                         ])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('map')
                 ->withConsecutive(
                     [$this->identicalTo($exportMachine1)],
                     [$this->identicalTo($exportMachine2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseMachine1,
                     $databaseMachine2
                 );
        $importer->expects($this->exactly(4))
                 ->method('add')
                 ->withConsecutive(
                     [$databaseMachine1],
                     [$databaseMachine2],
                     [$existingDatabaseMachine1],
                     [$existingDatabaseMachine2]
                 );

        $importer->parse($exportData);
    }
    
    /**
     * Tests the map method.
     * @throws ReflectionException
     * @covers ::map
     */
    public function testMap(): void
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

        $this->craftingCategoryImporter->expects($this->exactly(2))
                                       ->method('getByName')
                                       ->withConsecutive(
                                           [$this->identicalTo('ghi')],
                                           [$this->identicalTo('jkl')]
                                       )
                                       ->willReturnOnConsecutiveCalls(
                                           $craftingCategory1,
                                           $craftingCategory2
                                       );

        $this->idCalculator->expects($this->once())
                           ->method('calculateIdOfMachine')
                           ->with($this->equalTo($expectedDatabaseMachine))
                           ->willReturn($machineId);

        $this->validator->expects($this->once())
                        ->method('validateMachine')
                        ->with($this->equalTo($expectedDatabaseMachine));

        $importer = new MachineImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->machineRepository,
            $this->validator
        );
        $result = $this->invokeMethod($importer, 'map', $exportMachine);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the add method.
     * @throws ReflectionException
     * @covers ::add
     */
    public function testAdd(): void
    {
        $machineId = Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        $machine = new DatabaseMachine();
        $machine->setId($machineId);

        $expectedMachines = [
            '70acdb0f-36ca-4b30-9687-2baaade94cd3' => $machine,
        ];

        $importer = new MachineImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->machineRepository,
            $this->validator
        );
        $this->invokeMethod($importer, 'add', $machine);

        $this->assertSame($expectedMachines, $this->extractProperty($importer, 'machines'));
    }
    
    /**
     * Tests the persist method.
     * @throws ReflectionException
     * @covers ::persist
     */
    public function testPersist(): void
    {
        /* @var DatabaseMachine&MockObject $machine1 */
        $machine1 = $this->createMock(DatabaseMachine::class);
        /* @var DatabaseMachine&MockObject $machine2 */
        $machine2 = $this->createMock(DatabaseMachine::class);
        $machines = [$machine1, $machine2];

        /* @var Collection&MockObject $machineCollection */
        $machineCollection = $this->createMock(Collection::class);
        $machineCollection->expects($this->once())
                          ->method('clear');
        $machineCollection->expects($this->exactly(2))
                          ->method('add')
                          ->withConsecutive(
                              [$this->identicalTo($machine1)],
                              [$this->identicalTo($machine2)]
                          );

        /* @var DatabaseCombination&MockObject $combination */
        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->any())
                    ->method('getMachines')
                    ->willReturn($machineCollection);

        /* @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
                      ->method('persist')
                      ->withConsecutive(
                          [$this->identicalTo($machine1)],
                          [$this->identicalTo($machine2)]
                      );

        $importer = new MachineImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->machineRepository,
            $this->validator
        );
        $this->injectProperty($importer, 'machines', $machines);

        $importer->persist($entityManager, $combination);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $this->machineRepository->expects($this->once())
                                ->method('removeOrphans');

        $this->craftingCategoryImporter->expects($this->once())
                                       ->method('cleanup');

        $importer = new MachineImporter(
            $this->craftingCategoryImporter,
            $this->idCalculator,
            $this->machineRepository,
            $this->validator
        );
        $importer->cleanup();
    }
}
