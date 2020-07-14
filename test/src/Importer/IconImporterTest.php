<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Icon;
use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\IconRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\DataCollector;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\IconImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\Common\Constant\RecipeMode;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
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
     * The mocked repository.
     * @var IconRepository&MockObject
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
        $this->repository = $this->createMock(IconRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new IconImporter($this->dataCollector, $this->entityManager, $this->repository, $this->validator);

        $this->assertSame($this->dataCollector, $this->extractProperty($importer, 'dataCollector'));
        $this->assertSame($this->entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($this->repository, $this->extractProperty($importer, 'repository'));
        $this->assertSame($this->validator, $this->extractProperty($importer, 'validator'));
    }

    /**
     * Tests the prepare method.
     * @covers ::prepare
     */
    public function testPrepare(): void
    {
        $combinationId = $this->createMock(UuidInterface::class);

        $combination = new DatabaseCombination();
        $combination->setId($combinationId);

        $this->repository->expects($this->once())
                         ->method('clearCombination')
                         ->with($this->identicalTo($combinationId));

        $importer = new IconImporter($this->dataCollector, $this->entityManager, $this->repository, $this->validator);
        $importer->prepare($combination);
    }

    /**
     * Tests the getExportEntities method.
     * @throws ReflectionException
     * @covers ::getExportEntities
     */
    public function testGetExportEntities(): void
    {
        $mod1 = new Mod();
        $mod1->setName('abc')
             ->setThumbnailId('def');

        $mod2 = new Mod();
        $mod2->setName('ghi');

        $mod3 = new Mod();
        $mod3->setName('jkl')
             ->setThumbnailId('mno');

        $item1 = new Item();
        $item1->setType('pqr')
              ->setName('stu')
              ->setIconId('vwx');

        $item2 = new Item();
        $item2->setType('yza')
              ->setName('bcd');

        $item3 = new Item();
        $item3->setType('efg')
              ->setName('hij')
              ->setIconId('klm');

        $machine1 = new Machine();
        $machine1->setName('nop')
                 ->setIconId('qrs');

        $machine2 = new Machine();
        $machine2->setName('tuv');

        $machine3 = new Machine();
        $machine3->setName('wxy')
                 ->setIconId('zab');

        $recipe1 = new Recipe();
        $recipe1->setName('cde')
                ->setMode(RecipeMode::NORMAL)
                ->setIconId('fgh');

        $recipe2 = new Recipe();
        $recipe2->setName('ijk')
                ->setMode(RecipeMode::NORMAL);

        $recipe3 = new Recipe();
        $recipe3->setName('lmn')
                ->setMode(RecipeMode::NORMAL)
                ->setIconId('opq');

        $recipe4 = new Recipe();
        $recipe4->setName('rst')
                ->setMode(RecipeMode::EXPENSIVE)
                ->setIconId('uvw');


        $expectedResult = [
            [EntityType::MOD, 'abc', 'def'],
            [EntityType::MOD, 'jkl', 'mno'],
            ['pqr', 'stu', 'vwx'],
            ['efg', 'hij', 'klm'],
            [EntityType::MACHINE, 'nop', 'qrs'],
            [EntityType::MACHINE, 'wxy', 'zab'],
            [EntityType::RECIPE, 'cde', 'fgh'],
            [EntityType::RECIPE, 'lmn', 'opq'],
        ];

        $combination = new ExportCombination();
        $combination->setMods([$mod1, $mod2, $mod3])
                    ->setItems([$item1, $item2, $item3])
                    ->setMachines([$machine1, $machine2, $machine3])
                    ->setRecipes([$recipe1, $recipe2, $recipe3, $recipe4]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new IconImporter($this->dataCollector, $this->entityManager, $this->repository, $this->validator);
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals($expectedResult, iterator_to_array($result));
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        $exportData = $this->createMock(ExportData::class);
        $offset = 1337;
        $limit = 42;

        $data1 = ['abc', 'def', 'ghi'];
        $data2 = ['jkl', 'mno', 'pqr'];

        $icon1 = $this->createMock(Icon::class);
        $icon2 = $this->createMock(Icon::class);

        $iconsCollection = $this->createMock(Collection::class);
        $iconsCollection->expects($this->exactly(2))
                        ->method('add')
                        ->withConsecutive(
                            [$this->identicalTo($icon1)],
                            [$this->identicalTo($icon2)],
                        );

        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->any())
                    ->method('getIcons')
                    ->willReturn($iconsCollection);

        $this->entityManager->expects($this->exactly(2))
                            ->method('persist')
                            ->withConsecutive(
                                [$this->identicalTo($icon1)],
                                [$this->identicalTo($icon2)],
                            );
        $this->entityManager->expects($this->once())
                            ->method('flush');

        $importer = $this->getMockBuilder(IconImporter::class)
                         ->onlyMethods(['getChunkedExportEntities', 'createIcon'])
                         ->setConstructorArgs([
                             $this->dataCollector,
                             $this->entityManager,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getChunkedExportEntities')
                 ->with($this->identicalTo($exportData), $this->identicalTo($offset), $this->identicalTo($limit))
                 ->willReturn([$data1, $data2]);
        $importer->expects($this->exactly(2))
                 ->method('createIcon')
                 ->withConsecutive(
                     [$this->identicalTo($data1), $this->identicalTo($combination)],
                     [$this->identicalTo($data2), $this->identicalTo($combination)],
                 )
                 ->willReturnOnConsecutiveCalls(
                     $icon1,
                     $icon2,
                 );

        $importer->import($combination, $exportData, $offset, $limit);
    }
    
    /**
     * Tests the createIcon method.
     * @throws ReflectionException
     * @covers ::createIcon
     */
    public function testCreateIcon(): void
    {
        $type = 'abc';
        $name = 'def';
        $imageId = 'ghi';
        $data = [$type, $name, $imageId];

        $combination = $this->createMock(DatabaseCombination::class);
        $iconImage = $this->createMock(IconImage::class);

        $expectedResult = new DatabaseIcon();
        $expectedResult->setType('abc')
                       ->setName('def')
                       ->setImage($iconImage)
                       ->setCombination($combination);

        $this->dataCollector->expects($this->once())
                            ->method('getIconImage')
                            ->with($this->identicalTo($imageId))
                            ->willReturn($iconImage);

        $this->validator->expects($this->once())
                        ->method('validateIcon')
                        ->with($this->equalTo($expectedResult));

        $importer = new IconImporter($this->dataCollector, $this->entityManager, $this->repository, $this->validator);
        $result = $this->invokeMethod($importer, 'createIcon', $data, $combination);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $importer = new IconImporter($this->dataCollector, $this->entityManager, $this->repository, $this->validator);
        $importer->cleanup();

        $this->addToAssertionCount(1);
    }
}
