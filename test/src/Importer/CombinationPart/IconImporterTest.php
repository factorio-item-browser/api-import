<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\CombinationPart;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconFile;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\IconFileRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\CombinationPart\IconImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the IconImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\CombinationPart\IconImporter
 */
class IconImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @covers ::__construct
     * @throws ReflectionException
     */
    public function testConstruct(): void
    {
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var IconFileRepository $iconFileRepository */
        $iconFileRepository = $this->createMock(IconFileRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new IconImporter($entityManager, $iconFileRepository, $registryService);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($iconFileRepository, $this->extractProperty($importer, 'iconFileRepository'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        /* @var Collection $iconCollection */
        $iconCollection = $this->createMock(Collection::class);
        /* @var ExportCombination $exportCombination */
        $exportCombination = $this->createMock(ExportCombination::class);

        $newIcons = [
            $this->createMock(DatabaseIcon::class),
            $this->createMock(DatabaseIcon::class),
        ];
        $existingIcons = [
            $this->createMock(DatabaseIcon::class),
            $this->createMock(DatabaseIcon::class),
        ];
        $persistedIcons = [
            $this->createMock(DatabaseIcon::class),
            $this->createMock(DatabaseIcon::class),
        ];

        /* @var DatabaseCombination|MockObject $databaseCombination */
        $databaseCombination = $this->getMockBuilder(DatabaseCombination::class)
                                    ->setMethods(['getIcons'])
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $databaseCombination->expects($this->once())
                            ->method('getIcons')
                            ->willReturn($iconCollection);

        /* @var IconImporter|MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods([
                             'getIconsFromCombination',
                             'getExistingIcons',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();

        $importer->expects($this->once())
                 ->method('getIconsFromCombination')
                 ->with($exportCombination, $databaseCombination)
                 ->willReturn($newIcons);
        $importer->expects($this->once())
                 ->method('getExistingIcons')
                 ->with($databaseCombination)
                 ->willReturn($existingIcons);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newIcons, $existingIcons)
                 ->willReturn($persistedIcons);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedIcons, $iconCollection);

        $importer->import($exportCombination, $databaseCombination);
    }

    /**
     * Tests the getIconsFromCombination method.
     * @covers ::getIconsFromCombination
     * @throws ReflectionException
     */
    public function testGetIconsFromCombination(): void
    {
        /* @var DatabaseIcon $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon $icon3 */
        $icon3 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon $icon4 */
        $icon4 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon $icon5 */
        $icon5 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon $icon6 */
        $icon6 = $this->createMock(DatabaseIcon::class);

        $itemIcons = [
            'abc' => $icon1,
            'def' => $icon2,
        ];
        $machineIcons = [
            'ghi' => $icon3,
            'jkl' => $icon4,
        ];
        $recipeIcons = [
            'mno' => $icon5,
            'pqr' => $icon6,
        ];
        $expectedResult = [
            'abc' => $icon1,
            'def' => $icon2,
            'ghi' => $icon3,
            'jkl' => $icon4,
            'mno' => $icon5,
            'pqr' => $icon6,
        ];

        /* @var ExportCombination $exportCombination */
        $exportCombination = $this->createMock(ExportCombination::class);
        /* @var DatabaseCombination $databaseCombination */
        $databaseCombination = $this->createMock(DatabaseCombination::class);

        /* @var IconImporter|MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['getIconsForItems', 'getIconsForMachines', 'getIconsForRecipes'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getIconsForItems')
                 ->with($exportCombination)
                 ->willReturn($itemIcons);
        $importer->expects($this->once())
                 ->method('getIconsForMachines')
                 ->with($exportCombination)
                 ->willReturn($machineIcons);
        $importer->expects($this->once())
                 ->method('getIconsForRecipes')
                 ->with($exportCombination)
                 ->willReturn($recipeIcons);
        $this->injectProperty($importer, 'iconFiles', ['fail']);

        $result = $this->invokeMethod($importer, 'getIconsFromCombination', $exportCombination, $databaseCombination);

        $this->assertEquals($expectedResult, $result);
        $this->assertSame([], $this->extractProperty($importer, 'iconFiles'));
        $this->assertSame($databaseCombination, $this->extractProperty($importer, 'databaseCombination'));
    }

    /**
     * Tests the getIconsForItems method.
     * @throws ReflectionException
     * @covers ::getIconsForItems
     */
    public function testGetIconsForItems(): void
    {
        $exportCombination = (new ExportCombination())->setItemHashes(['abc', 'def', 'ghi']);
        $item1 = new ExportItem();
        $item1->setType('jkl')
              ->setName('mno')
              ->setIconHash('pqr');
        $item2 = new ExportItem();
        $item2->setType('stu')
              ->setName('vwx')
              ->setIconHash('yza');

        /* @var IconFile $iconFile1 */
        $iconFile1 = $this->createMock(IconFile::class);
        /* @var IconFile $iconFile2 */
        $iconFile2 = $this->createMock(IconFile::class);
        /* @var DatabaseIcon $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);

        $expectedResult = [
            'bcd' => $icon1,
            'efg' => $icon2,
        ];

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getItem'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(3))
                        ->method('getItem')
                        ->withConsecutive(
                            ['abc'],
                            ['def'],
                            ['ghi']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $item1,
                            new ExportItem(),
                            $item2
                        );

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var IconFileRepository $iconFileRepository */
        $iconFileRepository = $this->createMock(IconFileRepository::class);

        /* @var IconImporter|MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['getIconFile', 'createIcon', 'getIdentifier'])
                         ->setConstructorArgs([$entityManager, $iconFileRepository, $registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getIconFile')
                 ->withConsecutive(
                     ['pqr'],
                     ['yza']
                 )
                 ->willReturnOnConsecutiveCalls(
                     $iconFile1,
                     $iconFile2
                 );
        $importer->expects($this->exactly(2))
                 ->method('createIcon')
                 ->withConsecutive(
                     [$iconFile1, 'jkl', 'mno'],
                     [$iconFile2, 'stu', 'vwx']
                 )
                 ->willReturnOnConsecutiveCalls(
                     $icon1,
                     $icon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$icon1],
                     [$icon2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'bcd',
                     'efg'
                 );

        $result = $this->invokeMethod($importer, 'getIconsForItems', $exportCombination);

        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Tests the getIconsForMachines method.
     * @throws ReflectionException
     * @covers ::getIconsForMachines
     */
    public function testGetIconsForMachines(): void
    {
        $exportCombination = (new ExportCombination())->setMachineHashes(['abc', 'def', 'ghi']);
        $machine1 = new ExportMachine();
        $machine1->setName('jkl')
                 ->setIconHash('mno');
        $machine2 = new ExportMachine();
        $machine2->setName('pqr')
                 ->setIconHash('stu');

        /* @var IconFile $iconFile1 */
        $iconFile1 = $this->createMock(IconFile::class);
        /* @var IconFile $iconFile2 */
        $iconFile2 = $this->createMock(IconFile::class);
        /* @var DatabaseIcon $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);

        $expectedResult = [
            'vwx' => $icon1,
            'yza' => $icon2,
        ];

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
                            $machine1,
                            new ExportMachine(),
                            $machine2
                        );

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var IconFileRepository $iconFileRepository */
        $iconFileRepository = $this->createMock(IconFileRepository::class);

        /* @var IconImporter|MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['getIconFile', 'createIcon', 'getIdentifier'])
                         ->setConstructorArgs([$entityManager, $iconFileRepository, $registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getIconFile')
                 ->withConsecutive(
                     ['mno'],
                     ['stu']
                 )
                 ->willReturnOnConsecutiveCalls(
                     $iconFile1,
                     $iconFile2
                 );
        $importer->expects($this->exactly(2))
                 ->method('createIcon')
                 ->withConsecutive(
                     [$iconFile1, EntityType::MACHINE, 'jkl'],
                     [$iconFile2, EntityType::MACHINE, 'pqr']
                 )
                 ->willReturnOnConsecutiveCalls(
                     $icon1,
                     $icon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$icon1],
                     [$icon2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'vwx',
                     'yza'
                 );

        $result = $this->invokeMethod($importer, 'getIconsForMachines', $exportCombination);

        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Tests the getIconsForRecipes method.
     * @throws ReflectionException
     * @covers ::getIconsForRecipes
     */
    public function testGetIconsForRecipes(): void
    {
        $exportCombination = (new ExportCombination())->setRecipeHashes(['abc', 'def', 'ghi']);
        $recipe1 = new ExportRecipe();
        $recipe1->setName('jkl')
                ->setIconHash('mno');
        $recipe2 = new ExportRecipe();
        $recipe2->setName('pqr')
                ->setIconHash('stu');

        /* @var IconFile $iconFile1 */
        $iconFile1 = $this->createMock(IconFile::class);
        /* @var IconFile $iconFile2 */
        $iconFile2 = $this->createMock(IconFile::class);
        /* @var DatabaseIcon $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);

        $expectedResult = [
            'vwx' => $icon1,
            'yza' => $icon2,
        ];

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getRecipe'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(3))
                        ->method('getRecipe')
                        ->withConsecutive(
                            ['abc'],
                            ['def'],
                            ['ghi']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $recipe1,
                            new ExportRecipe(),
                            $recipe2
                        );

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var IconFileRepository $iconFileRepository */
        $iconFileRepository = $this->createMock(IconFileRepository::class);

        /* @var IconImporter|MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['getIconFile', 'createIcon', 'getIdentifier'])
                         ->setConstructorArgs([$entityManager, $iconFileRepository, $registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getIconFile')
                 ->withConsecutive(
                     ['mno'],
                     ['stu']
                 )
                 ->willReturnOnConsecutiveCalls(
                     $iconFile1,
                     $iconFile2
                 );
        $importer->expects($this->exactly(2))
                 ->method('createIcon')
                 ->withConsecutive(
                     [$iconFile1, EntityType::RECIPE, 'jkl'],
                     [$iconFile2, EntityType::RECIPE, 'pqr']
                 )
                 ->willReturnOnConsecutiveCalls(
                     $icon1,
                     $icon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$icon1],
                     [$icon2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'vwx',
                     'yza'
                 );

        $result = $this->invokeMethod($importer, 'getIconsForRecipes', $exportCombination);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Provides the data for the getIconFile test.
     * @return array
     */
    public function provideGetIconFile(): array
    {
        /* @var IconFile $iconFile1 */
        $iconFile1 = $this->createMock(IconFile::class);
        /* @var IconFile $iconFile2 */
        $iconFile2 = $this->createMock(IconFile::class);

        return [
            [
                ['abc' => $iconFile1],
                'def',
                $iconFile2,
                $iconFile2,
                ['abc' => $iconFile1, 'def' => $iconFile2]
            ],
            [
                ['abc' => $iconFile1, 'def' => $iconFile2],
                'def',
                null,
                $iconFile2,
                ['abc' => $iconFile1, 'def' => $iconFile2]
            ],
        ];
    }

    /**
     * Tests the getIconFile method.
     * @param array|DatabaseIcon[] $iconFiles
     * @param string $iconHash
     * @param IconFile|null $iconFile
     * @param IconFile $expectedResult
     * @param array|DatabaseIcon[] $expectedIconFiles
     * @throws ReflectionException
     * @covers ::getIconFile
     * @dataProvider provideGetIconFile
     */
    public function testGetIconFile(
        array $iconFiles,
        string $iconHash,
        ?IconFile $iconFile,
        IconFile $expectedResult,
        array $expectedIconFiles
    ): void {
        /* @var IconImporter|MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['fetchIconFile'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($iconFile === null ? $this->never() : $this->once())
                 ->method('fetchIconFile')
                 ->with($iconHash)
                 ->willReturn($iconFile);
        $this->injectProperty($importer, 'iconFiles', $iconFiles);

        $result = $this->invokeMethod($importer, 'getIconFile', $iconHash);

        $this->assertSame($expectedResult, $result);
        $this->assertSame($expectedIconFiles, $this->extractProperty($importer, 'iconFiles'));
    }

}