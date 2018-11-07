<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
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
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
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
        /* @var DatabaseMachine $databaseMachine1 */
        $databaseMachine1 = $this->createMock(DatabaseMachine::class);
        /* @var DatabaseMachine $databaseMachine2 */
        $databaseMachine2 = $this->createMock(DatabaseMachine::class);

        $machineHashes = ['abc', 'def'];
        $expectedResult = [
            'ghi' => $databaseMachine1,
            'jkl' => $databaseMachine2,
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
        $registryService->expects($this->exactly(2))
                        ->method('getMachine')
                        ->withConsecutive(
                            ['abc'],
                            ['def']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $exportMachine1,
                            $exportMachine2
                        );

        /* @var CraftingCategoryService $craftingCategoryService */
        $craftingCategoryService = $this->createMock(CraftingCategoryService::class);
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var MachineRepository $machineRepository */
        $machineRepository = $this->createMock(MachineRepository::class);

        /* @var MachineImporter|MockObject $importer */
        $importer = $this->getMockBuilder(MachineImporter::class)
                         ->setMethods(['mapMachine', 'getIdentifier'])
                         ->setConstructorArgs([
                             $craftingCategoryService,
                             $entityManager,
                             $machineRepository,
                             $registryService
                         ])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('mapMachine')
                 ->withConsecutive(
                     [$exportMachine1],
                     [$exportMachine2]
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
                     'ghi',
                     'jkl'
                 );

        $result = $this->invokeMethod($importer, 'getMachinesFromCombination', $exportCombination);
        $this->assertEquals($expectedResult, $result);
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

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var MachineRepository $machineRepository */
        $machineRepository = $this->createMock(MachineRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new MachineImporter($craftingCategoryService, $entityManager, $machineRepository, $registryService);
        $result = $this->invokeMethod($importer, 'mapMachine', $exportMachine);

        $this->assertEquals($expectedResult, $result);
    }
}
