<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\IconImageRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\MissingIconImageException;
use FactorioItemBrowser\Api\Import\Importer\IconImageImporter;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Icon;
use FactorioItemBrowser\ExportData\ExportData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the IconImageImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\IconImageImporter
 */
class IconImageImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked icon image repository.
     * @var IconImageRepository&MockObject
     */
    protected $iconImageRepository;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->iconImageRepository = $this->createMock(IconImageRepository::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new IconImageImporter($this->iconImageRepository);

        $this->assertSame($this->iconImageRepository, $this->extractProperty($importer, 'iconImageRepository'));
    }

    /**
     * Tests the prepare method.
     * @covers ::prepare
     */
    public function testPrepare(): void
    {
        /* @var UuidInterface&MockObject $imageId1 */
        $imageId1 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $imageId2 */
        $imageId2 = $this->createMock(UuidInterface::class);

        /* @var Icon&MockObject $exportIcon1 */
        $exportIcon1 = $this->createMock(Icon::class);
        /* @var Icon&MockObject $exportIcon2 */
        $exportIcon2 = $this->createMock(Icon::class);

        $combination = new ExportCombination();
        $combination->setIcons([$exportIcon1, $exportIcon2]);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->once())
                   ->method('getCombination')
                   ->willReturn($combination);

        $image1 = new IconImage();
        $image1->setId($imageId1);
        $image2 = new IconImage();
        $image2->setId($imageId2);

        /* @var IconImage&MockObject $existingImage1 */
        $existingImage1 = $this->createMock(IconImage::class);
        /* @var IconImage&MockObject $existingImage2 */
        $existingImage2 = $this->createMock(IconImage::class);

        $this->iconImageRepository->expects($this->once())
                                  ->method('findByIds')
                                  ->with($this->identicalTo([$imageId1, $imageId2]))
                                  ->willReturn([$existingImage1, $existingImage2]);

        /* @var IconImageImporter&MockObject $importer */
        $importer = $this->getMockBuilder(IconImageImporter::class)
                         ->onlyMethods(['create', 'add'])
                         ->setConstructorArgs([$this->iconImageRepository])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('create')
                 ->withConsecutive(
                     [$this->identicalTo($exportIcon1)],
                     [$this->identicalTo($exportIcon2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $image1,
                     $image2
                 );
        $importer->expects($this->exactly(4))
                 ->method('add')
                 ->withConsecutive(
                     [$image1],
                     [$image2],
                     [$existingImage1],
                     [$existingImage2]
                 );

        $importer->prepare($exportData);
    }

    /**
     * Tests the create method.
     * @throws ReflectionException
     * @covers ::create
     */
    public function testCreate(): void
    {
        $icon = new Icon();
        $icon->setId('70acdb0f-36ca-4b30-9687-2baaade94cd3')
             ->setRenderedSize(42);

        $expectedResult = new IconImage();
        $expectedResult->setId(Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3'))
                       ->setSize(42);

        $importer = new IconImageImporter($this->iconImageRepository);
        $result = $this->invokeMethod($importer, 'create', $icon);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the add method.
     * @throws ReflectionException
     * @covers ::add
     */
    public function testAdd(): void
    {
        $imageId = Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        $image = new IconImage();
        $image->setId($imageId);

        $expectedImages = [
            '70acdb0f-36ca-4b30-9687-2baaade94cd3' => $image,
        ];

        $importer = new IconImageImporter($this->iconImageRepository);
        $this->invokeMethod($importer, 'add', $image);

        $this->assertSame($expectedImages, $this->extractProperty($importer, 'images'));
    }

    /**
     * Tests the getById method.
     * @throws ImportException
     * @throws ReflectionException
     * @covers ::getById
     */
    public function testGetById(): void
    {
        $id = 'abc';

        /* @var IconImage&MockObject $image */
        $image = $this->createMock(IconImage::class);

        $images = [
            'abc' => $image,
        ];

        $importer = new IconImageImporter($this->iconImageRepository);
        $this->injectProperty($importer, 'images', $images);

        $result = $importer->getById($id);

        $this->assertSame($image, $result);
    }

    /**
     * Tests the getById method.
     * @throws ImportException
     * @covers ::getById
     */
    public function testGetByIdWithoutMatch(): void
    {
        $id = 'abc';

        $this->expectException(MissingIconImageException::class);

        $importer = new IconImageImporter($this->iconImageRepository);
        $importer->getById($id);
    }

    /**
     * Tests the parse method.
     * @covers ::parse
     */
    public function testParse(): void
    {
        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);

        $importer = new IconImageImporter($this->iconImageRepository);
        $importer->parse($exportData);

        $this->addToAssertionCount(1);
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

        $images = [$image1, $image2];

        /* @var DatabaseCombination&MockObject $combination */
        $combination = $this->createMock(DatabaseCombination::class);

        /* @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
                      ->method('persist')
                      ->withConsecutive(
                          [$this->identicalTo($image1)],
                          [$this->identicalTo($image2)]
                      );

        $importer = new IconImageImporter($this->iconImageRepository);
        $this->injectProperty($importer, 'images', $images);

        $importer->persist($entityManager, $combination);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $this->iconImageRepository->expects($this->once())
                                  ->method('removeOrphans');

        $importer = new IconImageImporter($this->iconImageRepository);
        $importer->cleanup();
    }
}
