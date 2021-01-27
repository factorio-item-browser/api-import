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
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the IconImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\IconImporter
 */
class IconImporterTest extends TestCase
{
    use ReflectionTrait;

    /** @var DataCollector&MockObject */
    private DataCollector $dataCollector;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var IconRepository&MockObject */
    private IconRepository $repository;
    /** @var Validator&MockObject */
    private Validator $validator;

    protected function setUp(): void
    {
        $this->dataCollector = $this->createMock(DataCollector::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(IconRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return IconImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): IconImporter
    {
        return $this->getMockBuilder(IconImporter::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->dataCollector,
                        $this->entityManager,
                        $this->repository,
                        $this->validator,
                    ])
                    ->getMock();
    }

    public function testPrepare(): void
    {
        $combinationId = $this->createMock(UuidInterface::class);

        $combination = new DatabaseCombination();
        $combination->setId($combinationId);

        $this->repository->expects($this->once())
                         ->method('clearCombination')
                         ->with($this->identicalTo($combinationId));

        $instance = $this->createInstance();
        $instance->prepare($combination);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetExportEntities(): void
    {
        $mod1 = new Mod();
        $mod1->name = 'abc';
        $mod1->thumbnailId = 'def';
        $mod2 = new Mod();
        $mod2->name = 'ghi';
        $mod3 = new Mod();
        $mod3->name = 'jkl';
        $mod3->thumbnailId = 'mno';

        $item1 = new Item();
        $item1->type = 'pqr';
        $item1->name = 'stu';
        $item1->iconId = 'vwx';
        $item2 = new Item();
        $item2->type = 'yza';
        $item2->name = 'bcd';
        $item3 = new Item();
        $item3->type = 'efg';
        $item3->name = 'hij';
        $item3->iconId = 'klm';

        $machine1 = new Machine();
        $machine1->name = 'nop';
        $machine1->iconId = 'qrs';
        $machine2 = new Machine();
        $machine2->name = 'tuv';
        $machine3 = new Machine();
        $machine3->name = 'wxy';
        $machine3->iconId = 'zab';

        $recipe1 = new Recipe();
        $recipe1->name = 'cde';
        $recipe1->mode = RecipeMode::NORMAL;
        $recipe1->iconId = 'fgh';
        $recipe2 = new Recipe();
        $recipe2->name = 'ijk';
        $recipe2->mode = RecipeMode::NORMAL;
        $recipe3 = new Recipe();
        $recipe3->name = 'lmn';
        $recipe3->mode = RecipeMode::NORMAL;
        $recipe3->iconId = 'opq';
        $recipe4 = new Recipe();
        $recipe4->name = 'rst';
        $recipe4->mode = RecipeMode::EXPENSIVE;
        $recipe4->iconId = 'uvw';

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

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getMods()->add($mod1)
                              ->add($mod2)
                              ->add($mod3);
        $exportData->getItems()->add($item1)
                               ->add($item2)
                               ->add($item3);
        $exportData->getMachines()->add($machine1)
                                  ->add($machine2)
                                  ->add($machine3);
        $exportData->getRecipes()->add($recipe1)
                                 ->add($recipe2)
                                 ->add($recipe3)
                                 ->add($recipe4);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getExportEntities', $exportData);

        $this->assertEquals($expectedResult, iterator_to_array($result));
    }

    /**
     * @throws ImportException
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

        $instance = $this->createInstance(['getChunkedExportEntities', 'createIcon']);
        $instance->expects($this->once())
                 ->method('getChunkedExportEntities')
                 ->with($this->identicalTo($exportData), $this->identicalTo($offset), $this->identicalTo($limit))
                 ->willReturn([$data1, $data2]);
        $instance->expects($this->exactly(2))
                 ->method('createIcon')
                 ->withConsecutive(
                     [$this->identicalTo($data1), $this->identicalTo($combination)],
                     [$this->identicalTo($data2), $this->identicalTo($combination)],
                 )
                 ->willReturnOnConsecutiveCalls(
                     $icon1,
                     $icon2,
                 );

        $instance->import($combination, $exportData, $offset, $limit);
    }

    /**
     * @throws ReflectionException
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

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'createIcon', $data, $combination);

        $this->assertEquals($expectedResult, $result);
    }

    public function testCleanup(): void
    {
        $instance = $this->createInstance();
        $instance->cleanup();

        $this->addToAssertionCount(1);
    }
}
