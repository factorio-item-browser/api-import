<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconFile;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\IconFileRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Mod\ThumbnailImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the ThumbnailImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Mod\ThumbnailImporter
 */
class ThumbnailImporterTest extends TestCase
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
        $importer = new ThumbnailImporter($this->entityManager, $this->iconFileRepository, $this->registryService);

        $this->assertSame($this->entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($this->iconFileRepository, $this->extractProperty($importer, 'iconFileRepository'));
        $this->assertSame($this->registryService, $this->extractProperty($importer, 'registryService'));
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        $thumbnailHash = '12ab34cd';

        /* @var DatabaseMod&MockObject $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);
        /* @var IconFile&MockObject $iconFile */
        $iconFile = $this->createMock(IconFile::class);

        /* @var Exportmod&MockObject $exportMod */
        $exportMod = $this->createMock(Exportmod::class);
        $exportMod->expects($this->atLeastOnce())
                  ->method('getThumbnailHash')
                  ->willReturn($thumbnailHash);

        /* @var ThumbnailImporter&MockObject $importer */
        $importer = $this->getMockBuilder(ThumbnailImporter::class)
                         ->setMethods(['fetchIconFile', 'processIconFile', 'flushEntities'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('fetchIconFile')
                 ->with($this->identicalTo($thumbnailHash))
                 ->willReturn($iconFile);
        $importer->expects($this->once())
                 ->method('processIconFile')
                 ->with($this->identicalTo($databaseMod), $this->identicalTo($iconFile));
        $importer->expects($this->once())
                 ->method('flushEntities');

        $importer->import($exportMod, $databaseMod);
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImportWithoutHash(): void
    {
        $thumbnailHash = '';

        /* @var DatabaseMod&MockObject $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);

        /* @var Exportmod&MockObject $exportMod */
        $exportMod = $this->createMock(Exportmod::class);
        $exportMod->expects($this->atLeastOnce())
                  ->method('getThumbnailHash')
                  ->willReturn($thumbnailHash);

        /* @var ThumbnailImporter&MockObject $importer */
        $importer = $this->getMockBuilder(ThumbnailImporter::class)
                         ->setMethods(['fetchIconFile', 'processIconFile', 'flushEntities'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMock();
        $importer->expects($this->never())
                 ->method('fetchIconFile');;
        $importer->expects($this->never())
                 ->method('processIconFile');
        $importer->expects($this->never())
                 ->method('flushEntities');

        $importer->import($exportMod, $databaseMod);
    }

    /**
     * Tests the processIconFile method.
     * @throws ReflectionException
     * @covers ::processIconFile
     */
    public function testProcessIconFileWithIcon(): void
    {
        /* @var DatabaseMod&MockObject $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);
        /* @var IconFile&MockObject $iconFile */
        $iconFile = $this->createMock(IconFile::class);

        /* @var DatabaseCombination&MockObject $baseCombination */
        $baseCombination = $this->createMock(DatabaseCombination::class);
        $baseCombination->expects($this->never())
                        ->method('getIcons');

        /* @var DatabaseIcon&MockObject $icon */
        $icon = $this->createMock(DatabaseIcon::class);
        $icon->expects($this->once())
             ->method('setFile')
             ->with($this->identicalTo($iconFile));

        /* @var ThumbnailImporter&MockObject $importer */
        $importer = $this->getMockBuilder(ThumbnailImporter::class)
                         ->setMethods(['getBaseCombination', 'getExistingThumbnailIcon', 'createIcon', 'persistEntity'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getBaseCombination')
                 ->with($this->identicalTo($databaseMod))
                 ->willReturn($baseCombination);
        $importer->expects($this->once())
                 ->method('getExistingThumbnailIcon')
                 ->with($this->identicalTo($baseCombination))
                 ->willReturn($icon);
        $importer->expects($this->never())
                 ->method('createIcon');
        $importer->expects($this->never())
                 ->method('persistEntity');

        $this->invokeMethod($importer, 'processIconFile', $databaseMod, $iconFile);
    }

    /**
     * Tests the processIconFile method.
     * @throws ReflectionException
     * @covers ::processIconFile
     */
    public function testProcessIconFileWithoutIcon(): void
    {
        $modName = 'abc';

        /* @var IconFile&MockObject $iconFile */
        $iconFile = $this->createMock(IconFile::class);
        /* @var DatabaseIcon&MockObject $newIcon */
        $newIcon = $this->createMock(DatabaseIcon::class);

        /* @var Collection&MockObject $iconCollection */
        $iconCollection = $this->createMock(Collection::class);
        $iconCollection->expects($this->once())
                       ->method('add')
                       ->with($this->identicalTo($newIcon));

        /* @var DatabaseCombination&MockObject $baseCombination */
        $baseCombination = $this->createMock(DatabaseCombination::class);
        $baseCombination->expects($this->once())
                        ->method('getIcons')
                        ->willReturn($iconCollection);

        /* @var DatabaseMod&MockObject $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);
        $databaseMod->expects($this->once())
                    ->method('getName')
                    ->willReturn($modName);

        /* @var ThumbnailImporter&MockObject $importer */
        $importer = $this->getMockBuilder(ThumbnailImporter::class)
                         ->setMethods(['getBaseCombination', 'getExistingThumbnailIcon', 'createIcon', 'persistEntity'])
                         ->setConstructorArgs([$this->entityManager, $this->iconFileRepository, $this->registryService])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getBaseCombination')
                 ->with($this->identicalTo($databaseMod))
                 ->willReturn($baseCombination);
        $importer->expects($this->once())
                 ->method('getExistingThumbnailIcon')
                 ->with($this->identicalTo($baseCombination))
                 ->willReturn(null);
        $importer->expects($this->once())
                 ->method('createIcon')
                 ->with(
                     $this->identicalTo($baseCombination),
                     $this->identicalTo($iconFile),
                     $this->identicalTo(EntityType::MOD),
                     $this->identicalTo($modName)
                 )
                 ->willReturn($newIcon);
        $importer->expects($this->once())
                 ->method('persistEntity')
                 ->with($this->identicalTo($newIcon));

        $this->invokeMethod($importer, 'processIconFile', $databaseMod, $iconFile);
    }

    /**
     * Tests the getBaseCombination method.
     * @throws ReflectionException
     * @covers ::getBaseCombination
     */
    public function testGetBaseCombination(): void
    {
        /* @var DatabaseCombination&MockObject $combination1 */
        $combination1 = $this->createMock(DatabaseCombination::class);
        $combination1->expects($this->once())
                     ->method('getOptionalModIds')
                     ->willReturn([1337, 42]);

        /* @var DatabaseCombination&MockObject $combination2 */
        $combination2 = $this->createMock(DatabaseCombination::class);
        $combination2->expects($this->once())
                     ->method('getOptionalModIds')
                     ->willReturn([]);

        $expectedResult = $combination2;

        /* @var DatabaseMod&MockObject $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);
        $databaseMod->expects($this->once())
                    ->method('getCombinations')
                    ->willReturn(new ArrayCollection([$combination1, $combination2]));

        $importer = new ThumbnailImporter($this->entityManager, $this->iconFileRepository, $this->registryService);
        $result = $this->invokeMethod($importer, 'getBaseCombination', $databaseMod);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getBaseCombination method.
     * @throws ReflectionException
     * @covers ::getBaseCombination
     */
    public function testGetBaseCombinationWithoutMatch(): void
    {
        /* @var DatabaseCombination&MockObject $combination1 */
        $combination1 = $this->createMock(DatabaseCombination::class);
        $combination1->expects($this->once())
                     ->method('getOptionalModIds')
                     ->willReturn([1337, 42]);

        /* @var DatabaseCombination&MockObject $combination2 */
        $combination2 = $this->createMock(DatabaseCombination::class);
        $combination2->expects($this->once())
                     ->method('getOptionalModIds')
                     ->willReturn([21]);

        /* @var DatabaseMod&MockObject $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);
        $databaseMod->expects($this->once())
                    ->method('getCombinations')
                    ->willReturn(new ArrayCollection([$combination1, $combination2]));

        $this->expectException(ImportException::class);

        $importer = new ThumbnailImporter($this->entityManager, $this->iconFileRepository, $this->registryService);
        $this->invokeMethod($importer, 'getBaseCombination', $databaseMod);
    }

    /**
     * Tests the getExistingThumbnailIcon method.
     * @throws ReflectionException
     * @covers ::getExistingThumbnailIcon
     */
    public function testGetExistingThumbnailIcon(): void
    {
        /* @var DatabaseIcon&MockObject $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        $icon1->expects($this->once())
              ->method('getType')
              ->willReturn('abc');

        /* @var DatabaseIcon&MockObject $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);
        $icon2->expects($this->once())
              ->method('getType')
              ->willReturn(EntityType::MOD);

        $expectedResult = $icon2;

        /* @var DatabaseCombination&MockObject $baseCombination */
        $baseCombination = $this->createMock(DatabaseCombination::class);
        $baseCombination->expects($this->once())
                        ->method('getIcons')
                        ->willReturn(new ArrayCollection([$icon1, $icon2]));

        $importer = new ThumbnailImporter($this->entityManager, $this->iconFileRepository, $this->registryService);
        $result = $this->invokeMethod($importer, 'getExistingThumbnailIcon', $baseCombination);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getExistingThumbnailIcon method.
     * @throws ReflectionException
     * @covers ::getExistingThumbnailIcon
     */
    public function testGetExistingThumbnailIconWithoutMatch(): void
    {
        /* @var DatabaseIcon&MockObject $icon1 */
        $icon1 = $this->createMock(DatabaseIcon::class);
        $icon1->expects($this->once())
              ->method('getType')
              ->willReturn('abc');

        /* @var DatabaseIcon&MockObject $icon2 */
        $icon2 = $this->createMock(DatabaseIcon::class);
        $icon2->expects($this->once())
              ->method('getType')
              ->willReturn('def');

        /* @var DatabaseCombination&MockObject $baseCombination */
        $baseCombination = $this->createMock(DatabaseCombination::class);
        $baseCombination->expects($this->once())
                        ->method('getIcons')
                        ->willReturn(new ArrayCollection([$icon1, $icon2]));

        $importer = new ThumbnailImporter($this->entityManager, $this->iconFileRepository, $this->registryService);
        $result = $this->invokeMethod($importer, 'getExistingThumbnailIcon', $baseCombination);

        $this->assertNull($result);
    }
}
