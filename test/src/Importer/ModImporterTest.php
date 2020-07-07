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
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\ModImporter;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

    /**
     * The mocked id calculator.
     * @var IdCalculator&MockObject
     */
    protected $idCalculator;

    /**
     * The mocked repository.
     * @var ModRepository&MockObject
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

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->repository = $this->createMock(ModRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new ModImporter($this->entityManager, $this->idCalculator, $this->repository, $this->validator);

        $this->assertSame($this->idCalculator, $this->extractProperty($importer, 'idCalculator'));
        $this->assertSame($this->validator, $this->extractProperty($importer, 'validator'));
    }

    /**
     * Tests the getCollectionFromCombination method.
     * @throws ReflectionException
     * @covers ::getCollectionFromCombination
     */
    public function testGetCollectionFromCombination(): void
    {
        $mods = $this->createMock(Collection::class);

        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->once())
                    ->method('getMods')
                    ->willReturn($mods);

        $importer = new ModImporter($this->entityManager, $this->idCalculator, $this->repository, $this->validator);
        $result = $this->invokeMethod($importer, 'getCollectionFromCombination', $combination);

        $this->assertSame($mods, $result);
    }

    /**
     * Tests the getExportEntities method.
     * @throws ReflectionException
     * @covers ::getExportEntities
     */
    public function testGetExportEntities(): void
    {
        $mod1 = $this->createMock(ExportMod::class);
        $mod2 = $this->createMock(ExportMod::class);
        $mod3 = $this->createMock(ExportMod::class);

        $combination = new ExportCombination();
        $combination->setMods([$mod1, $mod2, $mod3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new ModImporter($this->entityManager, $this->idCalculator, $this->repository, $this->validator);
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals([$mod1, $mod2, $mod3], iterator_to_array($result));
    }

    /**
     * Tests the createDatabaseEntity method.
     * @throws ReflectionException
     * @covers ::createDatabaseEntity
     */
    public function testCreateDatabaseEntity(): void
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

        $this->validator->expects($this->once())
                        ->method('validateMod')
                        ->with($this->equalTo($expectedDatabaseMod));

        $importer = new ModImporter($this->entityManager, $this->idCalculator, $this->repository, $this->validator);
        $result = $this->invokeMethod($importer, 'createDatabaseEntity', $exportMod);

        $this->assertEquals($expectedResult, $result);
    }
}
