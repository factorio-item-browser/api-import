<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Importer\ModImporter;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\ExportData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the ModImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\ModImporter
 */
class ModImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked id calculator.
     * @var IdCalculator&MockObject
     */
    protected $idCalculator;

    /**
     * The mocked mod repository.
     * @var ModRepository&MockObject
     */
    protected $modRepository;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->modRepository = $this->createMock(ModRepository::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new ModImporter($this->idCalculator, $this->modRepository);

        $this->assertSame($this->idCalculator, $this->extractProperty($importer, 'idCalculator'));
        $this->assertSame($this->modRepository, $this->extractProperty($importer, 'modRepository'));
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

        $importer = new ModImporter($this->idCalculator, $this->modRepository);
        $importer->prepare($exportData);

        $this->assertSame([], $this->extractProperty($importer, 'mods'));
    }

    /**
     * Tests the parse method.
     * @covers ::parse
     */
    public function testParse(): void
    {
        /* @var UuidInterface&MockObject $modId1 */
        $modId1 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $modId2 */
        $modId2 = $this->createMock(UuidInterface::class);

        /* @var ExportMod&MockObject $exportMod1 */
        $exportMod1 = $this->createMock(ExportMod::class);
        /* @var ExportMod&MockObject $exportMod2 */
        $exportMod2 = $this->createMock(ExportMod::class);

        $combination = new ExportCombination();
        $combination->setMods([$exportMod1, $exportMod2]);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->once())
                   ->method('getCombination')
                   ->willReturn($combination);

        /* @var DatabaseMod&MockObject $databaseMod1 */
        $databaseMod1 = $this->createMock(DatabaseMod::class);
        $databaseMod1->expects($this->any())
                      ->method('getId')
                      ->willReturn($modId1);

        /* @var DatabaseMod&MockObject $databaseMod2 */
        $databaseMod2 = $this->createMock(DatabaseMod::class);
        $databaseMod2->expects($this->any())
                      ->method('getId')
                      ->willReturn($modId2);

        /* @var DatabaseMod&MockObject $existingDatabaseMod1 */
        $existingDatabaseMod1 = $this->createMock(DatabaseMod::class);
        /* @var DatabaseMod&MockObject $existingDatabaseMod2 */
        $existingDatabaseMod2 = $this->createMock(DatabaseMod::class);

        $this->modRepository->expects($this->once())
                             ->method('findByIds')
                             ->with($this->identicalTo([$modId1, $modId2]))
                             ->willReturn([$existingDatabaseMod1, $existingDatabaseMod2]);

        /* @var ModImporter&MockObject $importer */
        $importer = $this->getMockBuilder(ModImporter::class)
                         ->onlyMethods(['map', 'add'])
                         ->setConstructorArgs([$this->idCalculator, $this->modRepository])
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('map')
                 ->withConsecutive(
                     [$this->identicalTo($exportMod1)],
                     [$this->identicalTo($exportMod2)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseMod1,
                     $databaseMod2
                 );
        $importer->expects($this->exactly(4))
                 ->method('add')
                 ->withConsecutive(
                     [$databaseMod1],
                     [$databaseMod2],
                     [$existingDatabaseMod1],
                     [$existingDatabaseMod2]
                 );

        $importer->parse($exportData);
    }

    /**
     * Tests the map method.
     * @throws ReflectionException
     * @covers ::map
     */
    public function testMap(): void
    {
        /* @var UuidInterface&MockObject $modId */
        $modId = $this->createMock(UuidInterface::class);

        $exportMod = new ExportMod();
        $exportMod->setName('abc')
                  ->setVersion('1.2.3')
                  ->setAuthor('def');

        $expectedDatabaseMod = new DatabaseMod();
        $expectedDatabaseMod->setName('abc')
                            ->setVersion('1.2.3')
                            ->setAuthor('def');

        $expectedResult = new DatabaseMod();
        $expectedResult->setId($modId)
                       ->setName('abc')
                       ->setVersion('1.2.3')
                       ->setAuthor('def');

        $this->idCalculator->expects($this->once())
                           ->method('calculateIdOfMod')
                           ->with($this->equalTo($expectedDatabaseMod))
                           ->willReturn($modId);

        $importer = new ModImporter($this->idCalculator, $this->modRepository);
        $result = $this->invokeMethod($importer, 'map', $exportMod);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the add method.
     * @throws ReflectionException
     * @covers ::add
     */
    public function testAdd(): void
    {
        $modId = Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        $mod = new DatabaseMod();
        $mod->setId($modId);

        $expectedMods = [
            '70acdb0f-36ca-4b30-9687-2baaade94cd3' => $mod,
        ];

        $importer = new ModImporter($this->idCalculator, $this->modRepository);
        $this->invokeMethod($importer, 'add', $mod);

        $this->assertSame($expectedMods, $this->extractProperty($importer, 'mods'));
    }

    /**
     * Tests the persist method.
     * @throws ReflectionException
     * @covers ::persist
     */
    public function testPersist(): void
    {
        /* @var DatabaseMod&MockObject $mod1 */
        $mod1 = $this->createMock(DatabaseMod::class);
        /* @var DatabaseMod&MockObject $mod2 */
        $mod2 = $this->createMock(DatabaseMod::class);
        $mods = [$mod1, $mod2];

        /* @var Collection&MockObject $modCollection */
        $modCollection = $this->createMock(Collection::class);
        $modCollection->expects($this->once())
                      ->method('clear');
        $modCollection->expects($this->exactly(2))
                      ->method('add')
                      ->withConsecutive(
                          [$this->identicalTo($mod1)],
                          [$this->identicalTo($mod2)]
                      );

        /* @var DatabaseCombination&MockObject $combination */
        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->any())
                    ->method('getMods')
                    ->willReturn($modCollection);

        /* @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
                      ->method('persist')
                      ->withConsecutive(
                          [$this->identicalTo($mod1)],
                          [$this->identicalTo($mod2)]
                      );

        $importer = new ModImporter($this->idCalculator, $this->modRepository);
        $this->injectProperty($importer, 'mods', $mods);

        $importer->persist($entityManager, $combination);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $this->modRepository->expects($this->once())
                            ->method('removeOrphans');

        $importer = new ModImporter($this->idCalculator, $this->modRepository);
        $importer->cleanup();
    }
}
