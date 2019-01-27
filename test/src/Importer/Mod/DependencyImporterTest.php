<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Constant\ModDependencyType;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModDependency as DatabaseDependency;
use FactorioItemBrowser\Api\Import\Database\ModService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\Entity\Mod\Dependency as ExportDependency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the DependencyImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporter
 */
class DependencyImporterTest extends TestCase
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

        $importer = new DependencyImporter($entityManager, $modService);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($modService, $this->extractProperty($importer, 'modService'));
    }
    
    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        $newDependencies = [
            $this->createMock(DatabaseDependency::class),
            $this->createMock(DatabaseDependency::class),
        ];
        $existingDependencies = [
            $this->createMock(DatabaseDependency::class),
            $this->createMock(DatabaseDependency::class),
        ];
        $persistedDependencies = [
            $this->createMock(DatabaseDependency::class),
            $this->createMock(DatabaseDependency::class),
        ];

        /* @var ExportMod $exportMod */
        $exportMod = $this->createMock(ExportMod::class);
        /* @var Collection $dependencyCollection */
        $dependencyCollection = $this->createMock(Collection::class);

        /* @var DatabaseMod|MockObject $databaseMod */
        $databaseMod = $this->getMockBuilder(DatabaseMod::class)
                            ->setMethods(['getDependencies'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $databaseMod->expects($this->once())
                    ->method('getDependencies')
                    ->willReturn($dependencyCollection);

        /* @var DependencyImporter|MockObject $importer */
        $importer = $this->getMockBuilder(DependencyImporter::class)
                         ->setMethods([
                             'getDependenciesFromMod',
                             'getExistingDependencies',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getDependenciesFromMod')
                 ->with($exportMod)
                 ->willReturn($newDependencies);
        $importer->expects($this->once())
                 ->method('getExistingDependencies')
                 ->with($newDependencies, $databaseMod)
                 ->willReturn($existingDependencies);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newDependencies, $existingDependencies)
                 ->willReturn($persistedDependencies);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedDependencies, $dependencyCollection);

        $importer->import($exportMod, $databaseMod);
    }

    /**
     * Tests the getDependenciesFromMod method.
     * @throws ReflectionException
     * @covers ::getDependenciesFromMod
     */
    public function testGetDependenciesFromMod(): void
    {
        /* @var ExportDependency $exportDependency1 */
        $exportDependency1 = $this->createMock(ExportDependency::class);
        /* @var ExportDependency $exportDependency2 */
        $exportDependency2 = $this->createMock(ExportDependency::class);
        /* @var DatabaseDependency $databaseDependency1 */
        $databaseDependency1 = $this->createMock(DatabaseDependency::class);
        /* @var DatabaseDependency $databaseDependency2 */
        $databaseDependency2 = $this->createMock(DatabaseDependency::class);

        $expectedResult = [
            'ghi' => $databaseDependency1,
            'jkl' => $databaseDependency2,
        ];

        /* @var DatabaseMod $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);

        /* @var ExportMod|MockObject $exportMod */
        $exportMod = $this->getMockBuilder(ExportMod::class)
                          ->setMethods(['getDependencies'])
                          ->disableOriginalConstructor()
                          ->getMock();
        $exportMod->expects($this->once())
                  ->method('getDependencies')
                  ->willReturn([$exportDependency1, $exportDependency2]);

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModService $modService */
        $modService = $this->createMock(ModService::class);

        /* @var DependencyImporter|MockObject $importer */
        $importer = $this->getMockBuilder(DependencyImporter::class)
                         ->setMethods(['mapDependency', 'getIdentifier'])
                         ->setConstructorArgs([$entityManager, $modService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('mapDependency')
                 ->withConsecutive(
                     [$exportDependency1, $databaseMod],
                     [$exportDependency2, $databaseMod]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseDependency1,
                     $databaseDependency2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$databaseDependency1],
                     [$databaseDependency2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'ghi',
                     'jkl'
                 );

        $result = $this->invokeMethod($importer, 'getDependenciesFromMod', $exportMod, $databaseMod);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Provides the data for the mapDependency test.
     * @return array
     */
    public function provideMapDependency(): array
    {
        return [
            [true, ModDependencyType::MANDATORY],
            [false, ModDependencyType::OPTIONAL],
        ];
    }

    /**
     * Tests the mapDependency method.
     * @param bool $isMandatory
     * @param string $expectedType
     * @covers ::mapDependency
     * @dataProvider provideMapDependency
     * @throws ReflectionException
     */
    public function testMapDependency(bool $isMandatory, string $expectedType): void
    {
        $databaseMod = new DatabaseMod('abc');
        $requiredMod = new DatabaseMod('def');

        $exportDependency = new ExportDependency();
        $exportDependency->setRequiredModName('ghi')
                         ->setRequiredVersion('1.2.3')
                         ->setIsMandatory($isMandatory);

        $expectedResult = new DatabaseDependency($databaseMod, $requiredMod);
        $expectedResult->setType($expectedType)
                       ->setRequiredVersion('1.2.3');

        /* @var ModService|MockObject $modService */
        $modService = $this->getMockBuilder(ModService::class)
                           ->setMethods(['getByName'])
                           ->disableOriginalConstructor()
                           ->getMock();
        $modService->expects($this->once())
                   ->method('getByName')
                   ->with('ghi')
                   ->willReturn($requiredMod);

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $importer = new DependencyImporter($entityManager, $modService);
        $result = $this->invokeMethod($importer, 'mapDependency', $exportDependency, $databaseMod);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getExistingDependencies method.
     * @throws ReflectionException
     * @covers ::getExistingDependencies
     */
    public function testGetExistingDependencies(): void
    {
        /* @var DatabaseDependency $newDependency */
        $newDependency = $this->createMock(DatabaseDependency::class);
        $newDependencies = ['abc' => $newDependency];

        /* @var DatabaseDependency $existingDependency1 */
        $existingDependency1 = $this->createMock(DatabaseDependency::class);
        /* @var DatabaseDependency $existingDependency2 */
        $existingDependency2 = $this->createMock(DatabaseDependency::class);

        $expectedResult = [
            'abc' => $existingDependency1,
            'def' => $existingDependency2,
        ];

        /* @var DatabaseMod|MockObject $databaseMod */
        $databaseMod = $this->getMockBuilder(DatabaseMod::class)
                            ->setMethods(['getDependencies'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $databaseMod->expects($this->once())
                    ->method('getDependencies')
                    ->willReturn(new ArrayCollection([$existingDependency1, $existingDependency2]));

        /* @var DependencyImporter|MockObject $importer */
        $importer = $this->getMockBuilder(DependencyImporter::class)
                         ->setMethods(['getIdentifier', 'applyChanges'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->exactly(2))
                         ->method('getIdentifier')
                         ->withConsecutive(
                             [$existingDependency1],
                             [$existingDependency2]
                         )
                         ->willReturnOnConsecutiveCalls(
                             'abc',
                             'def'
                         );
        $importer->expects($this->once())
                 ->method('applyChanges')
                 ->with($newDependency, $existingDependency1);

        $result = $this->invokeMethod($importer, 'getExistingDependencies', $newDependencies, $databaseMod);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the applyChanges method.
     * @throws ReflectionException
     * @covers ::applyChanges
     */
    public function testApplyChanges(): void
    {
        $type = 'abc';
        $requiredVersion = '1.2.3';

        /* @var DatabaseDependency|MockObject $source */
        $source = $this->getMockBuilder(DatabaseDependency::class)
                       ->setMethods(['getType', 'getRequiredVersion'])
                       ->disableOriginalConstructor()
                       ->getMock();
        $source->expects($this->once())
               ->method('getType')
               ->willReturn($type);
        $source->expects($this->once())
               ->method('getRequiredVersion')
               ->willReturn($requiredVersion);

        /* @var DatabaseDependency|MockObject $destination */
        $destination = $this->getMockBuilder(DatabaseDependency::class)
                            ->setMethods(['setType', 'setRequiredVersion'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $destination->expects($this->once())
                    ->method('setType')
                    ->with($type)
                    ->willReturnSelf();
        $destination->expects($this->once())
                    ->method('setRequiredVersion')
                    ->with($requiredVersion)
                    ->willReturnSelf();

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModService $modService */
        $modService = $this->createMock(ModService::class);

        $importer = new DependencyImporter($entityManager, $modService);
        $this->invokeMethod($importer, 'applyChanges', $source, $destination);
    }

    /**
     * Tests the getIdentifier method.
     * @throws ReflectionException
     * @covers ::getIdentifier
     */
    public function testGetIdentifier(): void
    {
        $dependency = new DatabaseDependency(new DatabaseMod('foo'), new DatabaseMod('abc'));
        $expectedResult = 'abc';

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModService $modService */
        $modService = $this->createMock(ModService::class);

        $importer = new DependencyImporter($entityManager, $modService);
        $result = $this->invokeMethod($importer, 'getIdentifier', $dependency);

        $this->assertSame($expectedResult, $result);
    }
}
