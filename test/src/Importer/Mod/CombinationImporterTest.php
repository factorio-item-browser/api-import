<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Import\Database\ModService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Mod\CombinationImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the CombinationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Mod\CombinationImporter
 */
class CombinationImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModService $modService */
        $modService = $this->createMock(ModService::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new CombinationImporter($entityManager, $modService, $registryService);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($modService, $this->extractProperty($importer, 'modService'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
    }
    
    /**
     * Tests the import method.
     * @throws ImportException
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        $newCombinations = [
            $this->createMock(DatabaseCombination::class),
            $this->createMock(DatabaseCombination::class),
        ];
        $existingCombinations = [
            $this->createMock(DatabaseCombination::class),
            $this->createMock(DatabaseCombination::class),
        ];
        $persistedCombinations = [
            $this->createMock(DatabaseCombination::class),
            $this->createMock(DatabaseCombination::class),
        ];

        /* @var ExportMod $exportMod */
        $exportMod = $this->createMock(ExportMod::class);
        /* @var Collection $combinationCollection */
        $combinationCollection = $this->createMock(Collection::class);

        /* @var DatabaseMod|MockObject $databaseMod */
        $databaseMod = $this->getMockBuilder(DatabaseMod::class)
                            ->setMethods(['getCombinations'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $databaseMod->expects($this->once())
                    ->method('getCombinations')
                    ->willReturn($combinationCollection);

        /* @var CombinationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CombinationImporter::class)
                         ->setMethods([
                             'getCombinationsFromMod',
                             'getExistingCombinations',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getCombinationsFromMod')
                 ->with($exportMod)
                 ->willReturn($newCombinations);
        $importer->expects($this->once())
                 ->method('getExistingCombinations')
                 ->with($newCombinations, $databaseMod)
                 ->willReturn($existingCombinations);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newCombinations, $existingCombinations)
                 ->willReturn($persistedCombinations);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedCombinations, $combinationCollection);

        $importer->import($exportMod, $databaseMod);
    }

    /**
     * Tests the getCombinationsFromMod method.
     * @throws ReflectionException
     * @covers ::getCombinationsFromMod
     */
    public function testGetCombinationsFromMod(): void
    {
        /* @var ExportCombination $exportCombination1 */
        $exportCombination1 = $this->createMock(ExportCombination::class);
        /* @var ExportCombination $exportCombination2 */
        $exportCombination2 = $this->createMock(ExportCombination::class);
        /* @var DatabaseCombination $databaseCombination1 */
        $databaseCombination1 = $this->createMock(DatabaseCombination::class);
        /* @var DatabaseCombination $databaseCombination2 */
        $databaseCombination2 = $this->createMock(DatabaseCombination::class);

        $combinationHashes = ['abc', 'def'];
        $expectedResult = [
            'ghi' => $databaseCombination1,
            'jkl' => $databaseCombination2,
        ];

        /* @var DatabaseMod $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);

        /* @var ExportMod|MockObject $exportMod */
        $exportMod = $this->getMockBuilder(ExportMod::class)
                                  ->setMethods(['getCombinationHashes'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportMod->expects($this->once())
                          ->method('getCombinationHashes')
                          ->willReturn($combinationHashes);

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getCombination'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(2))
                        ->method('getCombination')
                        ->withConsecutive(
                            ['abc'],
                            ['def']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $exportCombination1,
                            $exportCombination2
                        );

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModService $modService */
        $modService = $this->createMock(ModService::class);

        /* @var CombinationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CombinationImporter::class)
                         ->setMethods(['mapCombination', 'getIdentifier'])
                         ->setConstructorArgs([$entityManager, $modService, $registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('mapCombination')
                 ->withConsecutive(
                     [$exportCombination1, $databaseMod],
                     [$exportCombination2, $databaseMod]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseCombination1,
                     $databaseCombination2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$databaseCombination1],
                     [$databaseCombination2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'ghi',
                     'jkl'
                 );

        $result = $this->invokeMethod($importer, 'getCombinationsFromMod', $exportMod, $databaseMod);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the mapCombination method.
     * @throws ReflectionException
     * @covers ::mapCombination
     */
    public function testMapCombination(): void
    {
        /* @var DatabaseMod $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);

        $exportCombination = new ExportCombination();
        $exportCombination->setName('abc')
                          ->setLoadedOptionalModNames(['def', 'ghi']);

        $mod1 = new DatabaseMod('jkl');
        $mod1->setId(1337);
        $mod2 = new DatabaseMod('mno');
        $mod2->setId(42);

        $expectedResult = new DatabaseCombination($databaseMod, 'abc');
        $expectedResult->setOptionalModIds([42, 1337]);

        /* @var ModService|MockObject $modService */
        $modService = $this->getMockBuilder(ModService::class)
                           ->setMethods(['getByName'])
                           ->disableOriginalConstructor()
                           ->getMock();
        $modService->expects($this->exactly(2))
                   ->method('getByName')
                   ->withConsecutive(
                       ['def'],
                       ['ghi']
                   )
                   ->willReturnOnConsecutiveCalls(
                       $mod1,
                       $mod2
                   );

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new CombinationImporter($entityManager, $modService, $registryService);
        $result = $this->invokeMethod($importer, 'mapCombination', $exportCombination, $databaseMod);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getExistingCombinations method.
     * @throws ReflectionException
     * @covers ::getExistingCombinations
     */
    public function testGetExistingCombinations(): void
    {
        /* @var DatabaseCombination $newCombination */
        $newCombination = $this->createMock(DatabaseCombination::class);
        $newCombinations = ['abc' => $newCombination];

        /* @var DatabaseCombination $existingCombination1 */
        $existingCombination1 = $this->createMock(DatabaseCombination::class);
        /* @var DatabaseCombination $existingCombination2 */
        $existingCombination2 = $this->createMock(DatabaseCombination::class);

        $expectedResult = [
            'abc' => $existingCombination1,
            'def' => $existingCombination2,
        ];

        /* @var DatabaseMod|MockObject $databaseMod */
        $databaseMod = $this->getMockBuilder(DatabaseMod::class)
                            ->setMethods(['getCombinations'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $databaseMod->expects($this->once())
                    ->method('getCombinations')
                    ->willReturn(new ArrayCollection([$existingCombination1, $existingCombination2]));

        /* @var CombinationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CombinationImporter::class)
                         ->setMethods(['getIdentifier', 'applyChanges'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->exactly(2))
                         ->method('getIdentifier')
                         ->withConsecutive(
                             [$existingCombination1],
                             [$existingCombination2]
                         )
                         ->willReturnOnConsecutiveCalls(
                             'abc',
                             'def'
                         );
        $importer->expects($this->once())
                 ->method('applyChanges')
                 ->with($newCombination, $existingCombination1);

        $result = $this->invokeMethod($importer, 'getExistingCombinations', $newCombinations, $databaseMod);

        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Tests the applyChanges method.
     * @throws ReflectionException
     * @covers ::applyChanges
     */
    public function testApplyChanges(): void
    {
        $optionalModIds = [42, 1337];

        /* @var DatabaseCombination|MockObject $source */
        $source = $this->getMockBuilder(DatabaseCombination::class)
                       ->setMethods(['getOptionalModIds'])
                       ->disableOriginalConstructor()
                       ->getMock();
        $source->expects($this->once())
               ->method('getOptionalModIds')
               ->willReturn($optionalModIds);

        /* @var DatabaseCombination|MockObject $destination */
        $destination = $this->getMockBuilder(DatabaseCombination::class)
                            ->setMethods(['setOptionalModIds'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $destination->expects($this->once())
                    ->method('setOptionalModIds')
                    ->with($optionalModIds);

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModService $modService */
        $modService = $this->createMock(ModService::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new CombinationImporter($entityManager, $modService, $registryService);
        $this->invokeMethod($importer, 'applyChanges', $source, $destination);
    }

    /**
     * Tests the getIdentifier method.
     * @throws ReflectionException
     * @covers ::getIdentifier
     */
    public function testGetIdentifier(): void
    {
        $combination = new DatabaseCombination(new DatabaseMod('foo'), 'abc');
        $expectedResult = 'abc';

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModService $modService */
        $modService = $this->createMock(ModService::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new CombinationImporter($entityManager, $modService, $registryService);
        $result = $this->invokeMethod($importer, 'getIdentifier', $combination);

        $this->assertSame($expectedResult, $result);
    }
}
