<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\EntityWithId;
use FactorioItemBrowser\Api\Database\Repository\AbstractIdRepository;
use FactorioItemBrowser\Api\Database\Repository\AbstractIdRepositoryWithOrphans;
use FactorioItemBrowser\Api\Import\Importer\AbstractEntityImporter;
use FactorioItemBrowser\ExportData\ExportData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use stdClass;

/**
 * The PHPUnit test of the AbstractEntityImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\AbstractEntityImporter
 */
class AbstractEntityImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

    /**
     * The mocked repository.
     * @var AbstractIdRepository<stdClass>&MockObject
     */
    protected $repository;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(AbstractIdRepository::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var AbstractEntityImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractEntityImporter::class)
                         ->setConstructorArgs([$this->entityManager, $this->repository])
                         ->getMockForAbstractClass();

        $this->assertSame($this->entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($this->repository, $this->extractProperty($importer, 'repository'));
    }

    /**
     * Tests the prepare method.
     * @covers ::prepare
     */
    public function testPrepare(): void
    {
        $combination = $this->createMock(DatabaseCombination::class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
                   ->method('clear');

        $this->entityManager->expects($this->once())
                            ->method('flush');

        /* @var AbstractEntityImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractEntityImporter::class)
                         ->onlyMethods(['getCollectionFromCombination'])
                         ->setConstructorArgs([$this->entityManager, $this->repository])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('getCollectionFromCombination')
                 ->with($this->identicalTo($combination))
                 ->willReturn($collection);

        $importer->prepare($combination);
    }

    /**
     * Tests the import method.
     * @covers ::import
     */
    public function testImport(): void
    {
        $combination = $this->createMock(DatabaseCombination::class);
        $exportData = $this->createMock(ExportData::class);
        $offset = 1337;
        $limit = 42;

        $databaseEntities = [
            $this->createMock(stdClass::class),
            $this->createMock(stdClass::class),
        ];

        $entity1 = $this->createMock(stdClass::class);
        $entity2 = $this->createMock(stdClass::class);

        $fetchedEntities = [
            $entity1,
            $entity2,
        ];

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->exactly(2))
                   ->method('add')
                   ->withConsecutive(
                       [$this->identicalTo($entity1)],
                       [$this->identicalTo($entity2)],
                   );

        $this->entityManager->expects($this->exactly(2))
                            ->method('persist')
                            ->withConsecutive(
                                [$this->identicalTo($entity1)],
                                [$this->identicalTo($entity2)],
                            );
        $this->entityManager->expects($this->once())
                            ->method('flush');

        /* @var AbstractEntityImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractEntityImporter::class)
                         ->onlyMethods([
                             'prepareImport',
                             'getDatabaseEntities',
                             'fetchExistingEntities',
                             'getCollectionFromCombination',
                         ])
                         ->setConstructorArgs([$this->entityManager, $this->repository])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('prepareImport')
                 ->with(
                     $this->identicalTo($combination),
                     $this->identicalTo($exportData),
                     $this->identicalTo($offset),
                     $this->identicalTo($limit)
                 );
        $importer->expects($this->once())
                 ->method('getDatabaseEntities')
                 ->with($exportData, $offset, $limit)
                 ->willReturn($databaseEntities);
        $importer->expects($this->once())
                 ->method('fetchExistingEntities')
                 ->with($this->identicalTo($databaseEntities))
                 ->willReturn($fetchedEntities);
        $importer->expects($this->once())
                 ->method('getCollectionFromCombination')
                 ->with($this->identicalTo($combination))
                 ->willReturn($collection);

        $importer->import($combination, $exportData, $offset, $limit);
    }

    /**
     * Tests the prepareImport method.
     * @throws ReflectionException
     * @covers ::prepareImport
     */
    public function testPrepareImport(): void
    {
        $combination = $this->createMock(DatabaseCombination::class);
        $exportData = $this->createMock(ExportData::class);
        $offset = 1337;
        $limit = 42;

        /* @var AbstractEntityImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractEntityImporter::class)
                         ->setConstructorArgs([$this->entityManager, $this->repository])
                         ->getMockForAbstractClass();

        $this->invokeMethod($importer, 'prepareImport', $combination, $exportData, $offset, $limit);

        $this->addToAssertionCount(1);
    }

    /**
     * Tests the getDatabaseEntities method.
     * @throws ReflectionException
     * @covers ::getDatabaseEntities
     */
    public function testGetDatabaseEntities(): void
    {
        $exportData = $this->createMock(ExportData::class);
        $offset = 1337;
        $limit = 42;

        $exportEntity1 = $this->createMock(stdClass::class);
        $exportEntity2 = $this->createMock(stdClass::class);
        $exportEntities = [$exportEntity1, $exportEntity2];

        $databaseEntity1 = $this->createMock(EntityWithId::class);
        $databaseEntity2 = $this->createMock(EntityWithId::class);
        $expectedResult = [$databaseEntity1, $databaseEntity2];

        /* @var AbstractEntityImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractEntityImporter::class)
                         ->onlyMethods(['getChunkedExportEntities', 'createDatabaseEntity'])
                         ->setConstructorArgs([$this->entityManager, $this->repository])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('getChunkedExportEntities')
                 ->with($this->identicalTo($exportData), $this->identicalTo($offset), $this->identicalTo($limit))
                 ->willReturn($exportEntities);
        $importer->expects($this->exactly(2))
                 ->method('createDatabaseEntity')
                 ->withConsecutive(
                     [$this->identicalTo($exportEntity1)],
                     [$this->identicalTo($exportEntity2)],
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseEntity1,
                     $databaseEntity2,
                 );

        $result = $this->invokeMethod($importer, 'getDatabaseEntities', $exportData, $offset, $limit);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the fetchExistingEntities method.
     * @throws ReflectionException
     * @covers ::fetchExistingEntities
     */
    public function testFetchExistingEntities(): void
    {
        $id1 = '15e4392f-07b4-4fbe-982b-83e20fe5b429';
        $id2 = '20aa56ca-6cbd-4a53-85a5-56444c06762a';
        $id3 = '3f6c6965-25e0-493b-973b-286242862e5e';
        $expectedIds = [
            Uuid::fromString($id1),
            Uuid::fromString($id2),
            Uuid::fromString($id3),
        ];

        $entity1 = $this->createMock(EntityWithId::class);
        $entity1->expects($this->any())
                ->method('getId')
                ->willReturn(Uuid::fromString($id1));

        $entity2 = $this->createMock(EntityWithId::class);
        $entity2->expects($this->any())
                ->method('getId')
                ->willReturn(Uuid::fromString($id2));

        $entity3 = $this->createMock(EntityWithId::class);
        $entity3->expects($this->any())
                ->method('getId')
                ->willReturn(Uuid::fromString($id3));

        $entities = [$entity1, $entity2, $entity3];

        $existingEntity1 = $this->createMock(EntityWithId::class);
        $existingEntity1->expects($this->any())
                        ->method('getId')
                        ->willReturn(Uuid::fromString($id1));

        $existingEntity2 = $this->createMock(EntityWithId::class);
        $existingEntity2->expects($this->any())
                        ->method('getId')
                        ->willReturn(Uuid::fromString($id3));

        $existingEntities = [$existingEntity1, $existingEntity2];
        $expectedResult = [$existingEntity1, $entity2, $existingEntity2];

        $this->repository->expects($this->once())
                         ->method('findByIds')
                         ->with($this->equalTo($expectedIds))
                         ->willReturn($existingEntities);

        /* @var AbstractEntityImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractEntityImporter::class)
                         ->setConstructorArgs([$this->entityManager, $this->repository])
                         ->getMockForAbstractClass();

        $result = $this->invokeMethod($importer, 'fetchExistingEntities', $entities);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $repository = $this->createMock(AbstractIdRepositoryWithOrphans::class);
        $repository->expects($this->once())
                   ->method('removeOrphans');

        /* @var AbstractEntityImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractEntityImporter::class)
                         ->setConstructorArgs([$this->entityManager, $repository])
                         ->getMockForAbstractClass();

        $importer->cleanup();
    }
}
