<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\IconImageRepository;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\IconImageImporter;
use FactorioItemBrowser\ExportData\Collection\DictionaryInterface;
use FactorioItemBrowser\ExportData\Entity\Icon;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;

/**
 * The PHPUnit test of the IconImageImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\IconImageImporter
 */
class IconImageImporterTest extends TestCase
{
    use ReflectionTrait;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var IconImageRepository&MockObject */
    private IconImageRepository $repository;
    /** @var Validator&MockObject */
    private Validator $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(IconImageRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return IconImageImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): IconImageImporter
    {
        return $this->getMockBuilder(IconImageImporter::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->entityManager,
                        $this->repository,
                        $this->validator,
                    ])
                    ->getMock();
    }

    /**
     * @throws ReflectionException
     */
    public function testGetExportEntities(): void
    {
        $icon1 = $this->createMock(Icon::class);
        $icon2 = $this->createMock(Icon::class);
        $icon3 = $this->createMock(Icon::class);

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getIcons()->add($icon1)
                               ->add($icon2)
                               ->add($icon3);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getExportEntities', $exportData);

        $this->assertEquals([$icon1, $icon2, $icon3], iterator_to_array($result));
    }

    public function testPrepare(): void
    {
        $combination = $this->createMock(DatabaseCombination::class);

        $instance = $this->createInstance();
        $instance->prepare($combination);

        $this->addToAssertionCount(1);
    }

    public function testImport(): void
    {
        $exportData = $this->createMock(ExportData::class);
        $offset = 1337;
        $limit = 42;

        $exportIcon1 = $this->createMock(Icon::class);
        $exportIcon2 = $this->createMock(Icon::class);
        $exportIcons = [$exportIcon1, $exportIcon2];

        $iconImage1 = $this->createMock(IconImage::class);
        $iconImage2 = $this->createMock(IconImage::class);
        $iconImages = [$iconImage1, $iconImage2];

        $combination = $this->createMock(DatabaseCombination::class);

        $instance = $this->createInstance(['getChunkedExportEntities', 'createIconImage', 'persistIconImages']);
        $instance->expects($this->once())
                 ->method('getChunkedExportEntities')
                 ->with($this->identicalTo($exportData), $this->identicalTo($offset), $this->identicalTo($limit))
                 ->willReturn($exportIcons);
        $instance->expects($this->exactly(2))
                 ->method('createIconImage')
                 ->withConsecutive(
                     [$this->identicalTo($exportIcon1)],
                     [$this->identicalTo($exportIcon2)],
                 )
                 ->willReturnOnConsecutiveCalls(
                     $iconImage1,
                     $iconImage2,
                 );
        $instance->expects($this->once())
                 ->method('persistIconImages')
                 ->with($this->identicalTo($iconImages));

        $instance->import($combination, $exportData, $offset, $limit);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateIconImage(): void
    {
        $contents = 'abc';

        $exportIcon = new Icon();
        $exportIcon->id = '70acdb0f-36ca-4b30-9687-2baaade94cd3';
        $exportIcon->size = 42;

        $expectedResult = new IconImage();
        $expectedResult->setId(Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3'))
                       ->setSize(42)
                       ->setContents($contents);

        $renderedIcons = $this->createMock(DictionaryInterface::class);
        $renderedIcons->expects($this->once())
                      ->method('get')
                      ->with($this->identicalTo('70acdb0f-36ca-4b30-9687-2baaade94cd3'))
                      ->willReturn($contents);


        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->any())
                   ->method('getRenderedIcons')
                   ->willReturn($renderedIcons);

        $this->validator->expects($this->once())
                        ->method('validateIconImage')
                        ->with($this->equalTo($expectedResult));

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'createIconImage', $exportIcon, $exportData);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testPersistIconImages(): void
    {
        $id1 = '15e4392f-07b4-4fbe-982b-83e20fe5b429';
        $id2 = '20aa56ca-6cbd-4a53-85a5-56444c06762a';
        $id3 = '3f6c6965-25e0-493b-973b-286242862e5e';
        $expectedIds = [
            Uuid::fromString($id1),
            Uuid::fromString($id2),
            Uuid::fromString($id3),
        ];

        $iconImage1 = $this->createMock(IconImage::class);
        $iconImage1->expects($this->any())
                   ->method('getId')
                   ->willReturn(Uuid::fromString($id1));

        $iconImage2 = $this->createMock(IconImage::class);
        $iconImage2->expects($this->any())
                   ->method('getId')
                   ->willReturn(Uuid::fromString($id2));

        $iconImage3 = $this->createMock(IconImage::class);
        $iconImage3->expects($this->any())
                   ->method('getId')
                   ->willReturn(Uuid::fromString($id3));

        $iconImages = [$iconImage1, $iconImage2, $iconImage3];

        $existingIconImage1 = $this->createMock(IconImage::class);
        $existingIconImage1->expects($this->any())
                           ->method('getId')
                           ->willReturn(Uuid::fromString($id1));

        $existingIconImage2 = $this->createMock(IconImage::class);
        $existingIconImage2->expects($this->any())
                           ->method('getId')
                           ->willReturn(Uuid::fromString($id3));

        $existingIconImages = [$existingIconImage1, $existingIconImage2];

        $this->repository->expects($this->once())
                         ->method('findByIds')
                         ->with($this->equalTo($expectedIds))
                         ->willReturn($existingIconImages);

        $this->entityManager->expects($this->exactly(3))
                            ->method('persist')
                            ->withConsecutive(
                                [$this->identicalTo($existingIconImage1)],
                                [$this->identicalTo($iconImage2)],
                                [$this->identicalTo($existingIconImage2)],
                            );
        $this->entityManager->expects($this->once())
                            ->method('flush');

        $instance = $this->createInstance(['updateIconImage']);
        $instance->expects($this->exactly(2))
                 ->method('updateIconImage')
                 ->withConsecutive(
                     [$this->identicalTo($iconImage1), $this->identicalTo($existingIconImage1)],
                     [$this->identicalTo($iconImage3), $this->identicalTo($existingIconImage2)],
                 );

        $this->invokeMethod($instance, 'persistIconImages', $iconImages);
    }

    /**
     * @throws ReflectionException
     */
    public function testUpdateIconImage(): void
    {
        $source = new IconImage();
        $source->setSize(42)
               ->setContents('abc');

        $destination = $this->createMock(IconImage::class);
        $destination->expects($this->once())
                    ->method('setSize')
                    ->with($this->identicalTo(42))
                    ->willReturnSelf();
        $destination->expects($this->once())
                    ->method('setContents')
                    ->with($this->identicalTo('abc'))
                    ->willReturnSelf();

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'updateIconImage', $source, $destination);
    }

    public function testCleanup(): void
    {
        $this->repository->expects($this->once())
                         ->method('removeOrphans');

        $instance = $this->createInstance();
        $instance->cleanup();
    }
}
