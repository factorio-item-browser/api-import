<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Data\MachineData;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Machine as DatabaseMachine;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Import\Database\CraftingCategoryService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\MachineImporter;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the MachineImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Combination\MachineImporter
 */
class MachineImporterTest extends TestCase
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
        /* @var MachineRepository $machineRepository */
        $machineRepository = $this->createMock(MachineRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new MachineImporter($craftingCategoryService, $entityManager, $machineRepository, $registryService);

        $this->assertSame($craftingCategoryService, $this->extractProperty($importer, 'craftingCategoryService'));
        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($machineRepository, $this->extractProperty($importer, 'machineRepository'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
    }
    
    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        $newMachines = [
            $this->createMock(DatabaseMachine::class),
            $this->createMock(DatabaseMachine::class),
        ];
        $existingMachines = [
            $this->createMock(DatabaseMachine::class),
            $this->createMock(DatabaseMachine::class),
        ];
        $persistedMachines = [
            $this->createMock(DatabaseMachine::class),
            $this->createMock(DatabaseMachine::class),
        ];

        /* @var ExportCombination $exportCombination */
        $exportCombination = $this->createMock(ExportCombination::class);
        /* @var Collection $machineCollection */
        $machineCollection = $this->createMock(Collection::class);

        /* @var DatabaseCombination|MockObject $databaseCombination */
        $databaseCombination = $this->getMockBuilder(DatabaseCombination::class)
                                    ->setMethods(['getMachines'])
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $databaseCombination->expects($this->once())
                            ->method('getMachines')
                            ->willReturn($machineCollection);

        /* @var MachineImporter|MockObject $importer */
        $importer = $this->getMockBuilder(MachineImporter::class)
                         ->setMethods([
                             'getMachinesFromCombination',
                             'getExistingMachines',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getMachinesFromCombination')
                 ->with($exportCombination)
                 ->willReturn($newMachines);
        $importer->expects($this->once())
                 ->method('getExistingMachines')
                 ->with($newMachines)
                 ->willReturn($existingMachines);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newMachines, $existingMachines)
                 ->willReturn($persistedMachines);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedMachines, $machineCollection);

        $importer->import($exportCombination, $databaseCombination);
    }

    /**
     * Tests the getMachinesFromCombination method.
     * @throws ReflectionException
     * @covers ::getMachinesFromCombination
     */
    public function testGetMachinesFromCombination(): void
    {
        /* @var ExportMachine $exportMachine1 */
        $exportMachine1 = $this->createMock(ExportMachine::class);
        /* @var ExportMachine $exportMachine2 */
        $exportMachine2 = $this->createMock(ExportMachine::class);
        /* @var ExportMachine $exportMachine3 */
        $exportMachine3 = $this->createMock(ExportMachine::class);
        /* @var DatabaseMachine $databaseMachine1 */
        $databaseMachine1 = $this->createMock(DatabaseMachine::class);
        /* @var DatabaseMachine $databaseMachine2 */
        $databaseMachine2 = $this->createMock(DatabaseMachine::class);

        $machineHashes = ['abc', 'def', 'ghi'];
        $expectedResult = [
            'jkl' => $databaseMachine1,
            'mno' => $databaseMachine2,
        ];

        /* @var ExportCombination|MockObject $exportCombination */
        $exportCombination = $this->getMockBuilder(ExportCombination::class)
                                  ->setMethods(['getMachineHashes'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportCombination->expects($this->once())
                          ->method('getMachineHashes')
                          ->willReturn($machineHashes);

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getMachine'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(3))
                        ->method('getMachine')
                        ->withConsecutive(
                            ['abc'],
                            ['def'],
                            ['ghi']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $exportMachine1,
                            $exportMachine2,
                            $exportMachine3
                        );

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var MachineRepository $machineRepository */
        $machineRepository = $this->createMock(MachineRepository::class);

        /* @var MachineImporter|MockObject $importer */
        $importer = $this->getMockBuilder(MachineImporter::class)
                         ->setMethods(['hasMachineData', 'mapMachine', 'getIdentifier'])
                         ->setConstructorArgs([
                             $craftingCategoryService,
                             $entityManager,
                             $machineRepository,
                             $registryService
                         ])
                         ->getMock();
        $importer->expects($this->exactly(3))
                 ->method('hasMachineData')
                 ->withConsecutive(
                     [$exportMachine1],
                     [$exportMachine2],
                     [$exportMachine3]
                 )
                 ->willReturnOnConsecutiveCalls(
                     true,
                     false,
                     true
                 );
        $importer->expects($this->exactly(2))
                 ->method('mapMachine')
                 ->withConsecutive(
                     [$exportMachine1],
                     [$exportMachine3]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseMachine1,
                     $databaseMachine2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$databaseMachine1],
                     [$databaseMachine2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'jkl',
                     'mno'
                 );

        $result = $this->invokeMethod($importer, 'getMachinesFromCombination', $exportCombination);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Provides the data for the hasMachineData test.
     * @return array
     */
    public function provideHasMachineData(): array
    {
        $machine1 = new ExportMachine();
        $machine1->setCraftingCategories(['abc', 'def']);

        $machine2 = new ExportMachine();
        $machine2->setCraftingCategories([]);

        return [
            [$machine1, true],
            [$machine2, false],
        ];
    }

    /**
     * Tests the hasMachineData method.
     * @param ExportMachine $machine
     * @param bool $expectedResult
     * @throws ReflectionException
     * @covers ::hasMachineData
     * @dataProvider provideHasMachineData
     */
    public function testHasMachineData(ExportMachine $machine, bool $expectedResult): void
    {
        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var MachineRepository $machineRepository */
        $machineRepository = $this->createMock(MachineRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new MachineImporter($craftingCategoryService, $entityManager, $machineRepository, $registryService);
        $result = $this->invokeMethod($importer, 'hasMachineData', $machine);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the mapMachine method.
     * @throws ReflectionException
     * @covers ::mapMachine
     */
    public function testMapMachine(): void
    {
        /* @var CraftingCategory $craftingCategory1 */
        $craftingCategory1 = $this->createMock(CraftingCategory::class);
        /* @var CraftingCategory $craftingCategory2 */
        $craftingCategory2 = $this->createMock(CraftingCategory::class);

        $exportMachine = new ExportMachine();
        $exportMachine->setName('abc')
                      ->setCraftingSpeed(13.37)
                      ->setNumberOfItemSlots(12)
                      ->setNumberOfFluidInputSlots(23)
                      ->setNumberOfFluidOutputSlots(34)
                      ->setNumberOfModuleSlots(45)
                      ->setEnergyUsage(4.2)
                      ->setEnergyUsageUnit('def')
                      ->setCraftingCategories(['ghi', 'jkl']);

        $expectedResult = new DatabaseMachine('abc');
        $expectedResult->setCraftingSpeed(13.37)
                       ->setNumberOfItemSlots(12)
                       ->setNumberOfFluidInputSlots(23)
                       ->setNumberOfFluidOutputSlots(34)
                       ->setNumberOfModuleSlots(45)
                       ->setEnergyUsage(4.2)
                       ->setEnergyUsageUnit('def');
        $expectedResult->getCraftingCategories()->add($craftingCategory1);
        $expectedResult->getCraftingCategories()->add($craftingCategory2);

        /* @var CraftingCategoryService|MockObject $craftingCategoryService */
        $craftingCategoryService = $this->getMockBuilder(CraftingCategoryService::class)
                                        ->setMethods(['getByName'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $craftingCategoryService->expects($this->exactly(2))
                                ->method('getByName')
                                ->withConsecutive(
                                    ['ghi'],
                                    ['jkl']
                                )
                                ->willReturnOnConsecutiveCalls(
                                    $craftingCategory1,
                                    $craftingCategory2
                                );

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var MachineRepository $machineRepository */
        $machineRepository = $this->createMock(MachineRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new MachineImporter($craftingCategoryService, $entityManager, $machineRepository, $registryService);
        $result = $this->invokeMethod($importer, 'mapMachine', $exportMachine);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getExistingMachines method.
     * @throws ReflectionException
     * @covers ::getExistingMachines
     */
    public function testGetExistingMachines(): void
    {
        $machine1 = new DatabaseMachine('abc');
        $machine2 = new DatabaseMachine('def');
        $expectedNames = ['abc', 'def'];

        $machineData1 = (new MachineData())->setId(42);
        $machineData2 = (new MachineData())->setId(21);
        $expectedMachineIds = [42, 21];

        $existingMachine1 = new DatabaseMachine('ghi');
        $existingMachine2 = new DatabaseMachine('jkl');
        $expectedResult = [
            'mno' => $existingMachine1,
            'pqr' => $existingMachine2,
        ];

        /* @var MachineRepository|MockObject $machineRepository */
        $machineRepository = $this->getMockBuilder(MachineRepository::class)
                                  ->setMethods(['findDataByNames', 'findByIds'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $machineRepository->expects($this->once())
                          ->method('findDataByNames')
                          ->with($expectedNames)
                          ->willReturn([$machineData1, $machineData2]);
        $machineRepository->expects($this->once())
                          ->method('findByIds')
                          ->with($expectedMachineIds)
                          ->willReturn([$existingMachine1, $existingMachine2]);

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        /* @var MachineImporter|MockObject $importer */
        $importer = $this->getMockBuilder(MachineImporter::class)
                         ->setMethods(['getIdentifier'])
                         ->setConstructorArgs([
                             $craftingCategoryService,
                             $entityManager,
                             $machineRepository,
                             $registryService
                         ])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$existingMachine1],
                     [$existingMachine2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'mno',
                     'pqr'
                 );

        $result = $this->invokeMethod($importer, 'getExistingMachines', [$machine1, $machine2]);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getIdentifier method.
     * @throws ReflectionException
     * @covers ::getIdentifier
     */
    public function testGetIdentifier(): void
    {
        $craftingCategory1 = new CraftingCategory('jkl');
        $craftingCategory2 = new CraftingCategory('ghi');

        $machine = new DatabaseMachine('abc');
        $machine->setCraftingSpeed(13.37)
                ->setNumberOfItemSlots(12)
                ->setNumberOfFluidInputSlots(23)
                ->setNumberOfFluidOutputSlots(34)
                ->setNumberOfModuleSlots(45)
                ->setEnergyUsage(4.2)
                ->setEnergyUsageUnit('def');
        $machine->getCraftingCategories()->add($craftingCategory1);
        $machine->getCraftingCategories()->add($craftingCategory2);

        $expectedResult = EntityUtils::calculateHashOfArray([
            'abc',
            13.37,
            ['ghi', 'jkl'],
            12,
            23,
            34,
            45,
            4.2,
            'def',
        ]);

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var MachineRepository $machineRepository */
        $machineRepository = $this->createMock(MachineRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new MachineImporter($craftingCategoryService, $entityManager, $machineRepository, $registryService);
        $result = $this->invokeMethod($importer, 'getIdentifier', $machine);

        $this->assertSame($expectedResult, $result);
    }
}
