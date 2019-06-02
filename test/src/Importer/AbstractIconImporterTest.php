<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconFile;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\IconFileRepository;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\AbstractIconImporter;
use FactorioItemBrowser\ExportData\Entity\Icon as ExportIcon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the AbstractIconImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\AbstractIconImporter
 */
class AbstractIconImporterTest extends TestCase
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
     * @throws ReflectionException
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
        /* @var AbstractIconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractIconImporter::class)
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMockForAbstractClass();

        $this->assertSame($this->entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($this->iconFileRepository, $this->extractProperty($importer, 'iconFileRepository'));
        $this->assertSame($this->registryService, $this->extractProperty($importer, 'registryService'));
    }

    /**
     * Provides the data for the fetchIconFile test.
     * @return array
     */
    public function provideFetchIconFile(): array
    {
        return [
            [true, false],
            [false, true],
        ];
    }

    /**
     * Tests the fetchIconFile method.
     * @param bool $resultWithIcon
     * @param bool $expectCreate
     * @throws ReflectionException
     * @covers ::fetchIconFile
     * @dataProvider provideFetchIconFile
     */
    public function testFetchIconFile(bool $resultWithIcon, bool $expectCreate): void
    {
        $iconHash = 'ab12cd34';
        $image = 'abc';
        $size = 42;

        /* @var ExportIcon&MockObject $exportIcon */
        $exportIcon = $this->createMock(ExportIcon::class);
        $exportIcon->expects($this->once())
                   ->method('getSize')
                   ->willReturn($size);

        /* @var IconFile&MockObject $iconFile */
        $iconFile = $this->createMock(IconFile::class);
        $iconFile->expects($this->once())
                 ->method('setImage')
                 ->with($this->identicalTo($image))
                 ->willReturnSelf();
        $iconFile->expects($this->once())
                 ->method('setSize')
                 ->with($this->identicalTo($size))
                 ->willReturnSelf();

        $this->iconFileRepository->expects($this->once())
                                 ->method('findByHashes')
                                 ->with($this->identicalTo([$iconHash]))
                                 ->willReturn($resultWithIcon ? [$iconFile] : []);

        $this->registryService->expects($this->once())
                              ->method('getRenderedIcon')
                              ->with($this->identicalTo($iconHash))
                              ->willReturn($image);
        $this->registryService->expects($this->once())
                              ->method('getIcon')
                              ->with($this->identicalTo($iconHash))
                              ->willReturn($exportIcon);

        /* @var AbstractIconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractIconImporter::class)
                         ->setMethods(['createIconFile'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMockForAbstractClass();
        $importer->expects($expectCreate ? $this->once() : $this->never())
                 ->method('createIconFile')
                 ->with($this->identicalTo($iconHash))
                 ->willReturn($iconFile);

        $result = $this->invokeMethod($importer, 'fetchIconFile', $iconHash);

        $this->assertSame($iconFile, $result);
    }

        /**
     * Tests the createIconFile method.
     * @throws ReflectionException
     * @covers ::createIconFile
     */
    public function testCreateIconFile(): void
    {
        $iconHash = 'ab12cd34';
        $expectedResult = new IconFile('ab12cd34');

        /* @var AbstractIconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractIconImporter::class)
                         ->setMethods(['persistEntity'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('persistEntity')
                 ->with($this->equalTo($expectedResult));

        $result = $this->invokeMethod($importer, 'createIconFile', $iconHash);

        $this->assertEquals($expectedResult, $result);
    }


    /**
     * Tests the createIcon method.
     * @throws ReflectionException
     * @covers ::createIcon
     */
    public function testCreateIcon(): void
    {
        /* @var DatabaseCombination&MockObject $databaseCombination */
        $databaseCombination = $this->createMock(DatabaseCombination::class);
        /* @var IconFile&MockObject $iconFile */
        $iconFile = $this->createMock(IconFile::class);

        $type = 'abc';
        $name = 'def';

        $expectedResult = new DatabaseIcon($databaseCombination, $iconFile);
        $expectedResult->setType('abc')
                       ->setName('def');

        /* @var AbstractIconImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractIconImporter::class)
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMockForAbstractClass();

        $result = $this->invokeMethod($importer, 'createIcon', $databaseCombination, $iconFile, $type, $name);
        $this->assertEquals($expectedResult, $result);
    }
}
