<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\IconRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\IconImageImporter;
use FactorioItemBrowser\Api\Import\Importer\IconImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;

/**
 * The PHPUnit test of the IconImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\IconImporter
 */
class IconImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked icon image importer.
     * @var IconImageImporter&MockObject
     */
    protected $iconImageImporter;

    /**
     * The mocked icon repository.
     * @var IconRepository&MockObject
     */
    protected $iconRepository;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->iconImageImporter = $this->createMock(IconImageImporter::class);
        $this->iconRepository = $this->createMock(IconRepository::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new IconImporter($this->iconImageImporter, $this->iconRepository);

        $this->assertSame($this->iconImageImporter, $this->extractProperty($importer, 'iconImageImporter'));
        $this->assertSame($this->iconRepository, $this->extractProperty($importer, 'iconRepository'));
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

        $importer = new IconImporter($this->iconImageImporter, $this->iconRepository);
        $importer->prepare($exportData);

        $this->assertSame([], $this->extractProperty($importer, 'icons'));
    }

    /**
     * Tests the parse method.
     * @throws ImportException
     * @covers ::parse
     */
    public function testParse(): void
    {
        $mods = [
            $this->createMock(Mod::class),
            $this->createMock(Mod::class),
        ];
        $items = [
            $this->createMock(Item::class),
            $this->createMock(Item::class),
        ];
        $machines = [
            $this->createMock(Machine::class),
            $this->createMock(Machine::class),
        ];
        $recipes = [
            $this->createMock(Recipe::class),
            $this->createMock(Recipe::class),
        ];

        $combination = new ExportCombination();
        $combination->setMods($mods)
                    ->setItems($items)
                    ->setMachines($machines)
                    ->setRecipes($recipes);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->any())
                   ->method('getCombination')
                   ->willReturn($combination);

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->onlyMethods(['processMods', 'processItems', 'processMachines', 'processRecipes'])
                         ->setConstructorArgs([$this->iconImageImporter, $this->iconRepository])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('processMods')
                 ->with($this->identicalTo($mods));
        $importer->expects($this->once())
                 ->method('processItems')
                 ->with($this->identicalTo($items));
        $importer->expects($this->once())
                 ->method('processMachines')
                 ->with($this->identicalTo($machines));
        $importer->expects($this->once())
                 ->method('processRecipes')
                 ->with($this->identicalTo($recipes));

        $importer->parse($exportData);
    }

    /**
     * Tests the processMods method.
     * @throws ReflectionException
     * @covers ::processMods
     */
    public function testProcessMods(): void
    {
        $mod1 = new Mod();
        $mod1->setName('abc')
             ->setThumbnailId('def');
        $mod2 = new Mod();
        $mod2->setName('ghi')
             ->setThumbnailId('');
        $mod3 = new Mod();
        $mod3->setName('jkl')
             ->setThumbnailId('mno');

        /* @var DatabaseIcon&MockObject $databaseIcon1 */
        $databaseIcon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $databaseIcon2 */
        $databaseIcon2 = $this->createMock(DatabaseIcon::class);

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->onlyMethods(['create', 'add'])
                         ->setConstructorArgs([$this->iconImageImporter, $this->iconRepository])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('create')
                 ->withConsecutive(
                     [$this->identicalTo(EntityType::MOD), $this->identicalTo('abc'), $this->identicalTo('def')],
                     [$this->identicalTo(EntityType::MOD), $this->identicalTo('jkl'), $this->identicalTo('mno')]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseIcon1,
                     $databaseIcon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('add')
                 ->withConsecutive(
                     [$this->identicalTo($databaseIcon1)],
                     [$this->identicalTo($databaseIcon2)]
                 );

        $this->invokeMethod($importer, 'processMods', [$mod1, $mod2, $mod3]);
    }
    
    /**
     * Tests the processItems method.
     * @throws ReflectionException
     * @covers ::processItems
     */
    public function testProcessItems(): void
    {
        $item1 = new Item();
        $item1->setType('abc')
              ->setName('def')
              ->setIconId('ghi');
        $item2 = new Item();
        $item2->setType('jkl')
              ->setName('mno')
              ->setIconId('');
        $item3 = new Item();
        $item3->setType('pqr')
              ->setName('stu')
              ->setIconId('vwx');

        /* @var DatabaseIcon&MockObject $databaseIcon1 */
        $databaseIcon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $databaseIcon2 */
        $databaseIcon2 = $this->createMock(DatabaseIcon::class);

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->onlyMethods(['create', 'add'])
                         ->setConstructorArgs([$this->iconImageImporter, $this->iconRepository])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('create')
                 ->withConsecutive(
                     [$this->identicalTo('abc'), $this->identicalTo('def'), $this->identicalTo('ghi')],
                     [$this->identicalTo('pqr'), $this->identicalTo('stu'), $this->identicalTo('vwx')]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseIcon1,
                     $databaseIcon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('add')
                 ->withConsecutive(
                     [$this->identicalTo($databaseIcon1)],
                     [$this->identicalTo($databaseIcon2)]
                 );

        $this->invokeMethod($importer, 'processItems', [$item1, $item2, $item3]);
    }
    
    /**
     * Tests the processMachines method.
     * @throws ReflectionException
     * @covers ::processMachines
     */
    public function testProcessMachines(): void
    {
        $machine1 = new Machine();
        $machine1->setName('abc')
                 ->setIconId('def');
        $machine2 = new Machine();
        $machine2->setName('ghi')
                 ->setIconId('');
        $machine3 = new Machine();
        $machine3->setName('jkl')
                 ->setIconId('mno');

        /* @var DatabaseIcon&MockObject $databaseIcon1 */
        $databaseIcon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $databaseIcon2 */
        $databaseIcon2 = $this->createMock(DatabaseIcon::class);

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->onlyMethods(['create', 'add'])
                         ->setConstructorArgs([$this->iconImageImporter, $this->iconRepository])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('create')
                 ->withConsecutive(
                     [$this->identicalTo(EntityType::MACHINE), $this->identicalTo('abc'), $this->identicalTo('def')],
                     [$this->identicalTo(EntityType::MACHINE), $this->identicalTo('jkl'), $this->identicalTo('mno')]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseIcon1,
                     $databaseIcon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('add')
                 ->withConsecutive(
                     [$this->identicalTo($databaseIcon1)],
                     [$this->identicalTo($databaseIcon2)]
                 );

        $this->invokeMethod($importer, 'processMachines', [$machine1, $machine2, $machine3]);
    }
    
    /**
     * Tests the processRecipes method.
     * @throws ReflectionException
     * @covers ::processRecipes
     */
    public function testProcessRecipes(): void
    {
        $recipe1 = new Recipe();
        $recipe1->setName('abc')
                ->setIconId('def');
        $recipe2 = new Recipe();
        $recipe2->setName('ghi')
                ->setIconId('');
        $recipe3 = new Recipe();
        $recipe3->setName('jkl')
                ->setIconId('mno');

        /* @var DatabaseIcon&MockObject $databaseIcon1 */
        $databaseIcon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $databaseIcon2 */
        $databaseIcon2 = $this->createMock(DatabaseIcon::class);

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->onlyMethods(['create', 'add'])
                         ->setConstructorArgs([$this->iconImageImporter, $this->iconRepository])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('create')
                 ->withConsecutive(
                     [$this->identicalTo(EntityType::RECIPE), $this->identicalTo('abc'), $this->identicalTo('def')],
                     [$this->identicalTo(EntityType::RECIPE), $this->identicalTo('jkl'), $this->identicalTo('mno')]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseIcon1,
                     $databaseIcon2
                 );
        $importer->expects($this->exactly(2))
                 ->method('add')
                 ->withConsecutive(
                     [$this->identicalTo($databaseIcon1)],
                     [$this->identicalTo($databaseIcon2)]
                 );

        $this->invokeMethod($importer, 'processRecipes', [$recipe1, $recipe2, $recipe3]);
    }

    /**
     * Tests the create method.
     * @throws ReflectionException
     * @covers ::create
     */
    public function testCreate(): void
    {
        $type = 'abc';
        $name = 'def';
        $imageId = 'ghi';

        /* @var IconImage&MockObject $iconImage */
        $iconImage = $this->createMock(IconImage::class);

        $expectedResult = new DatabaseIcon();
        $expectedResult->setType('abc')
                       ->setName('def')
                       ->setImage($iconImage);

        $this->iconImageImporter->expects($this->once())
                                ->method('getById')
                                ->with($this->identicalTo($imageId))
                                ->willReturn($iconImage);
        
        $importer = new IconImporter($this->iconImageImporter, $this->iconRepository);
        $result = $this->invokeMethod($importer, 'create', $type, $name, $imageId);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the add method.
     * @throws ReflectionException
     * @covers ::add
     */
    public function testAdd(): void
    {
        $icon = new DatabaseIcon();
        $icon->setType('abc')
             ->setName('def');

        $expectedIcons = [
            'abc' => [
                'def' => $icon,
            ],
        ];

        $importer = new IconImporter($this->iconImageImporter, $this->iconRepository);
        $this->invokeMethod($importer, 'add', $icon);

        $this->assertSame($expectedIcons, $this->extractProperty($importer, 'icons'));
    }

    /**
     * Tests the persist method.
     * @throws ReflectionException
     * @covers ::persist
     */
    public function testPersist(): void
    {
        /* @var IconImage&MockObject $image1 */
        $image1 = $this->createMock(IconImage::class);
        /* @var IconImage&MockObject $image2 */
        $image2 = $this->createMock(IconImage::class);

        /* @var DatabaseIcon&MockObject $newIcon1 */
        $newIcon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $newIcon2 */
        $newIcon2 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $removedIcon1 */
        $removedIcon1 = $this->createMock(DatabaseIcon::class);
        /* @var DatabaseIcon&MockObject $removedIcon2 */
        $removedIcon2 = $this->createMock(DatabaseIcon::class);

        /* @var DatabaseIcon&MockObject $existingIcon1a */
        $existingIcon1a = $this->createMock(DatabaseIcon::class);
        $existingIcon1a->expects($this->once())
                       ->method('getImage')
                       ->willReturn($image1);

        /* @var DatabaseIcon&MockObject $existingIcon1b */
        $existingIcon1b = $this->createMock(DatabaseIcon::class);
        $existingIcon1b->expects($this->once())
                       ->method('setImage')
                       ->with($this->identicalTo($image1));

        /* @var DatabaseIcon&MockObject $existingIcon2a */
        $existingIcon2a = $this->createMock(DatabaseIcon::class);
        $existingIcon2a->expects($this->once())
                       ->method('getImage')
                       ->willReturn($image2);

        /* @var DatabaseIcon&MockObject $existingIcon2b */
        $existingIcon2b = $this->createMock(DatabaseIcon::class);
        $existingIcon2b->expects($this->once())
                       ->method('setImage')
                       ->with($this->identicalTo($image2));

        $icons = [
            'abc' => [
                'def' => $newIcon1,
                'ghi' => $existingIcon1a,
            ],
            'jkl' => [
                'mno' => $newIcon2,
                'pqr' => $existingIcon2a,
            ],
        ];

        $combination = new DatabaseCombination();
        $this->injectProperty($combination, 'icons', new ArrayCollection([
            $existingIcon1b,
            $existingIcon2b,
            $removedIcon1,
            $removedIcon2,
        ]));

        /* @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
                      ->method('persist')
                      ->withConsecutive(
                          [$this->identicalTo($newIcon1)],
                          [$this->identicalTo($newIcon2)]
                      );
        $entityManager->expects($this->exactly(2))
                      ->method('remove')
                      ->withConsecutive(
                          [$this->identicalTo($removedIcon1)],
                          [$this->identicalTo($removedIcon2)]
                      );

        /* @var IconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImporter::class)
                         ->onlyMethods(['getKeyForIcon'])
                         ->setConstructorArgs([$this->iconImageImporter, $this->iconRepository])
                         ->getMock();
        $importer->expects($this->any())
                 ->method('getKeyForIcon')
                 ->willReturnMap([
                     [$existingIcon1a, 'existing1'],
                     [$existingIcon1b, 'existing1'],
                     [$existingIcon2a, 'existing2'],
                     [$existingIcon2b, 'existing2'],
                     [$newIcon1, 'new1'],
                     [$newIcon2, 'new2'],
                     [$removedIcon1, 'removed1'],
                     [$removedIcon2, 'removed2'],
                 ]);
        $this->injectProperty($importer, 'icons', $icons);

        $importer->persist($entityManager, $combination);
    }

    /**
     * Tests the getKeyForIcon method.
     * @throws ReflectionException
     * @covers ::getKeyForIcon
     */
    public function testGetKeyForIcon(): void
    {
        $combination = new DatabaseCombination();
        $combination->setId(Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3'));

        $icon = new DatabaseIcon();
        $icon->setCombination($combination)
             ->setType('abc')
             ->setName('def');

        $expectedResult = '70acdb0f-36ca-4b30-9687-2baaade94cd3|abc|def';

        $importer = new IconImporter($this->iconImageImporter, $this->iconRepository);
        $result = $this->invokeMethod($importer, 'getKeyForIcon', $icon);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $importer = new IconImporter($this->iconImageImporter, $this->iconRepository);
        $importer->cleanup();

        $this->addToAssertionCount(1);
    }
}
