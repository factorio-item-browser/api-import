<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\Icon;
use FactorioItemBrowser\Api\Database\Entity\IconFile;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Repository\IconFileRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\IconImporter;
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
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Combination\IconImporter
 */
class IconImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

    /**
     * The mocked icon file repository.
     * @var IconFileRepository&MockObject
     */
    protected $iconFileRepository;

    /**
     * The mocked registry service.
     * @var RegistryService&MockObject
     */
    protected $registryService;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->iconFileRepository = $this->createMock(IconFileRepository::class);
        $this->registryService = $this->createMock(RegistryService::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new IconImporter($this->entityManager, $this->iconFileRepository, $this->registryService);

        $this->assertSame($this->entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($this->iconFileRepository, $this->extractProperty($importer, 'iconFileRepository'));
        $this->assertSame($this->registryService, $this->extractProperty($importer, 'registryService'));
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        /* @var Collection&MockObject $iconCollection */
        $iconCollection = $this->createMock(Collection::class);
        /* @var ExportCombination&MockObject $exportCombination */
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

        /* @var DatabaseCombination&MockObject $databaseCombination */
        $databaseCombination = $this->createMock(DatabaseCombination::class);
        $databaseCombination->expects($this->once())
                            ->method('getIcons')
                            ->willReturn($iconCollection);

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods([
                             'getIconsFromCombination',
                             'getExistingIcons',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMock();

        $importer->expects($this->once())
                 ->method('getIconsFromCombination')
                 ->with($exportCombination, $databaseCombination)
                 ->willReturn($newIcons);
        $importer->expects($this->once())
                 ->method('getExistingIcons')
                 ->with($newIcons, $databaseCombination)
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
        /* @var DatabaseIcon&MockObject $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $icon3 */
        $icon3 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $icon4 */
        $icon4 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $icon5 */
        $icon5 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $icon6 */
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

        /* @var ExportCombination&MockObject $exportCombination */
        $exportCombination = $this->createMock(ExportCombination::class);
        /* @var DatabaseCombination&MockObject $databaseCombination */
        $databaseCombination = $this->createMock(DatabaseCombination::class);

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['getIconsForItems', 'getIconsForMachines', 'getIconsForRecipes'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
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

        /* @var DatabaseCombination&MockObject $databaseCombination */
        $databaseCombination = $this->createMock(DatabaseCombination::class);
        /* @var IconFile&MockObject $iconFile1 */
        $iconFile1 = $this->createMock(IconFile::class);
        /* @var IconFile&MockObject $iconFile2 */
        $iconFile2 = $this->createMock(IconFile::class);
        /* @var DatabaseIcon&MockObject $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);

        $expectedResult = [
            'bcd' => $icon1,
            'efg' => $icon2,
        ];

        $this->registryService->expects($this->exactly(3))
                              ->method('getItem')
                              ->withConsecutive(
                                  [$this->identicalTo('abc')],
                                  [$this->identicalTo('def')],
                                  [$this->identicalTo('ghi')]
                              )
                              ->willReturnOnConsecutiveCalls(
                                  $item1,
                                  new ExportItem(),
                                  $item2
                              );

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['getIconFile', 'createIcon', 'getIdentifier'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getIconFile')
                 ->withConsecutive(
                     [$this->identicalTo('pqr')],
                     [$this->identicalTo('yza')]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $iconFile1,
                     $iconFile2
                 );
        $importer->expects($this->exactly(2))
                 ->method('createIcon')
                 ->withConsecutive(
                     [
                         $this->identicalTo($databaseCombination),
                         $this->identicalTo($iconFile1),
                         $this->identicalTo('jkl'),
                         $this->identicalTo('mno')
                     ],
                     [
                         $this->identicalTo($databaseCombination),
                         $this->identicalTo($iconFile2),
                         $this->identicalTo('stu'),
                         $this->identicalTo('vwx')
                     ]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $icon1,
                     $icon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$this->identicalTo($icon1)],
                     [$this->identicalTo($icon2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'bcd',
                     'efg'
                 );
        $this->injectProperty($importer, 'databaseCombination', $databaseCombination);

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

        /* @var DatabaseCombination&MockObject $databaseCombination */
        $databaseCombination = $this->createMock(DatabaseCombination::class);
        /* @var IconFile&MockObject $iconFile1 */
        $iconFile1 = $this->createMock(IconFile::class);
        /* @var IconFile&MockObject $iconFile2 */
        $iconFile2 = $this->createMock(IconFile::class);
        /* @var DatabaseIcon&MockObject $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);

        $expectedResult = [
            'vwx' => $icon1,
            'yza' => $icon2,
        ];

        $this->registryService->expects($this->exactly(3))
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

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['getIconFile', 'createIcon', 'getIdentifier'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getIconFile')
                 ->withConsecutive(
                     [$this->identicalTo('mno')],
                     [$this->identicalTo('stu')]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $iconFile1,
                     $iconFile2
                 );
        $importer->expects($this->exactly(2))
                 ->method('createIcon')
                 ->withConsecutive(
                     [
                         $this->identicalTo($databaseCombination),
                         $this->identicalTo($iconFile1),
                         $this->identicalTo(EntityType::MACHINE),
                         $this->identicalTo('jkl'),
                     ],
                     [
                         $this->identicalTo($databaseCombination),
                         $this->identicalTo($iconFile2),
                         $this->identicalTo(EntityType::MACHINE),
                         $this->identicalTo('pqr'),
                     ]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $icon1,
                     $icon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$this->identicalTo($icon1)],
                     [$this->identicalTo($icon2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'vwx',
                     'yza'
                 );
        $this->injectProperty($importer, 'databaseCombination', $databaseCombination);

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

        /* @var DatabaseCombination&MockObject $databaseCombination */
        $databaseCombination = $this->createMock(DatabaseCombination::class);
        /* @var IconFile&MockObject $iconFile1 */
        $iconFile1 = $this->createMock(IconFile::class);
        /* @var IconFile&MockObject $iconFile2 */
        $iconFile2 = $this->createMock(IconFile::class);
        /* @var DatabaseIcon&MockObject $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);

        $expectedResult = [
            'vwx' => $icon1,
            'yza' => $icon2,
        ];

        $this->registryService->expects($this->exactly(3))
                              ->method('getRecipe')
                              ->withConsecutive(
                                  [$this->identicalTo('abc')],
                                  [$this->identicalTo('def')],
                                  [$this->identicalTo('ghi')]
                              )
                              ->willReturnOnConsecutiveCalls(
                                  $recipe1,
                                  new ExportRecipe(),
                                  $recipe2
                              );

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['getIconFile', 'createIcon', 'getIdentifier'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
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
                     [
                         $this->identicalTo($databaseCombination),
                         $this->identicalTo($iconFile1),
                         $this->identicalTo(EntityType::RECIPE),
                         $this->identicalTo('jkl'),
                     ],
                     [
                         $this->identicalTo($databaseCombination),
                         $this->identicalTo($iconFile2),
                         $this->identicalTo(EntityType::RECIPE),
                         $this->identicalTo('pqr'),
                     ]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $icon1,
                     $icon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$this->identicalTo($icon1)],
                     [$this->identicalTo($icon2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'vwx',
                     'yza'
                 );
        $this->injectProperty($importer, 'databaseCombination', $databaseCombination);

        $result = $this->invokeMethod($importer, 'getIconsForRecipes', $exportCombination);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Provides the data for the getIconFile test.
     * @return array
     * @throws ReflectionException
     */
    public function provideGetIconFile(): array
    {
        /* @var IconFile&MockObject $iconFile1 */
        $iconFile1 = $this->createMock(IconFile::class);
        /* @var IconFile&MockObject $iconFile2 */
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
        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['fetchIconFile'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMock();
        if ($iconFile === null) {
            $importer->expects($this->never())
                     ->method('fetchIconFile');
        } else {
            $importer->expects($this->once())
                     ->method('fetchIconFile')
                     ->with($iconHash)
                     ->willReturn($iconFile);
        }

        $this->injectProperty($importer, 'iconFiles', $iconFiles);

        $result = $this->invokeMethod($importer, 'getIconFile', $iconHash);

        $this->assertSame($expectedResult, $result);
        $this->assertSame($expectedIconFiles, $this->extractProperty($importer, 'iconFiles'));
    }

    /**
     * Tests the getExistingIcons method.
     * @throws ReflectionException
     * @covers ::getExistingIcons
     */
    public function testGetExistingIcons(): void
    {
        /* @var DatabaseIcon&MockObject $newIcon */
        $newIcon = $this->createMock(DatabaseIcon::class);
        $newIcons = [
            'abc' => $newIcon,
        ];

        /* @var DatabaseIcon&MockObject $existingIcon1 */
        $existingIcon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $existingIcon2 */
        $existingIcon2 = $this->createMock(DatabaseIcon::class);
        $expectedResult = [
            'abc' => $existingIcon1,
            'def' => $existingIcon2,
        ];

        /* @var DatabaseCombination&MockObject $databaseCombination */
        $databaseCombination = $this->getMockBuilder(DatabaseCombination::class)
                                    ->setMethods(['getIcons'])
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $databaseCombination->expects($this->once())
                            ->method('getIcons')
                            ->willReturn(new ArrayCollection([$existingIcon1, $existingIcon2]));

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->setMethods(['getIdentifier', 'applyChanges'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getIdentifier')
                 ->withConsecutive(
                     [$existingIcon1],
                     [$existingIcon2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'abc',
                     'def'
                 );
        $importer->expects($this->once())
                 ->method('applyChanges')
                 ->with($newIcon, $existingIcon1);

        $result = $this->invokeMethod($importer, 'getExistingIcons', $newIcons, $databaseCombination);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the applyChanges method.
     * @throws ReflectionException
     * @covers ::applyChanges
     */
    public function testApplyChanges(): void
    {
        /* @var IconFile&MockObject $iconFile */
        $iconFile = $this->createMock(IconFile::class);

        /* @var DatabaseIcon&MockObject $source */
        $source = $this->createMock(DatabaseIcon::class);
        $source->expects($this->once())
               ->method('getFile')
               ->willReturn($iconFile);

        /* @var DatabaseIcon&MockObject $destination */
        $destination = $this->createMock(DatabaseIcon::class);
        $destination->expects($this->once())
                    ->method('setFile')
                    ->with($iconFile);

        $importer = new IconImporter($this->entityManager, $this->iconFileRepository, $this->registryService);

        $this->invokeMethod($importer, 'applyChanges', $source, $destination);
    }

    /**
     * Tests the getIdentifier method.
     * @throws ReflectionException
     * @covers ::getIdentifier
     */
    public function testGetIdentifier(): void
    {
        /* @var Icon $icon */
        $icon = new Icon(new ModCombination(new Mod('abc'), 'def'), new IconFile('ab12cd34'));
        $icon->setType('foo')
             ->setName('bar');
        $expectedResult = 'foo|bar';

        $importer = new IconImporter($this->entityManager, $this->iconFileRepository, $this->registryService);

        $result = $this->invokeMethod($importer, 'getIdentifier', $icon);
        $this->assertSame($expectedResult, $result);
    }
}
