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
 * @covers \FactorioItemBrowser\Api\Import\Importer\AbstractEntityImporter
 */
class AbstractEntityImporterTest extends TestCase
{
    use ReflectionTrait;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var AbstractIdRepository<stdClass>&MockObject */
    private AbstractIdRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(AbstractIdRepository::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return AbstractEntityImporter<mixed, EntityWithId>&MockObject
     */
    private function createInstance(array $mockedMethods = []): AbstractEntityImporter
    {
        return $this->getMockBuilder(AbstractEntityImporter::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->entityManager,
                        $this->repository,
                    ])
                    ->getMockForAbstractClass();
    }

    public function testPrepare(): void
    {
        $combination = $this->createMock(DatabaseCombination::class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
                   ->method('clear');

        $this->entityManager->expects($this->once())
                            ->method('flush');

        $instance = $this->createInstance(['getCollectionFromCombination']);
        $instance->expects($this->once())
                 ->method('getCollectionFromCombination')
                 ->with($this->identicalTo($combination))
                 ->willReturn($collection);

        $instance->prepare($combination);
    }

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

        $instance = $this->createInstance([
                             'prepareImport',
                             'getDatabaseEntities',
                             'fetchExistingEntities',
                             'getCollectionFromCombination',
                         ]);
        $instance->expects($this->once())
                 ->method('prepareImport')
                 ->with(
                     $this->identicalTo($combination),
                     $this->identicalTo($exportData),
                     $this->identicalTo($offset),
                     $this->identicalTo($limit)
                 );
        $instance->expects($this->once())
                 ->method('getDatabaseEntities')
                 ->with($exportData, $offset, $limit)
                 ->willReturn($databaseEntities);
        $instance->expects($this->once())
                 ->method('fetchExistingEntities')
                 ->with($this->identicalTo($databaseEntities))
                 ->willReturn($fetchedEntities);
        $instance->expects($this->once())
                 ->method('getCollectionFromCombination')
                 ->with($this->identicalTo($combination))
                 ->willReturn($collection);

        $instance->import($combination, $exportData, $offset, $limit);
    }

    /**
     * @throws ReflectionException
     */
    public function testPrepareImport(): void
    {
        $combination = $this->createMock(DatabaseCombination::class);
        $exportData = $this->createMock(ExportData::class);
        $offset = 1337;
        $limit = 42;

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'prepareImport', $combination, $exportData, $offset, $limit);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws ReflectionException
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

        $instance = $this->createInstance(['getChunkedExportEntities', 'createDatabaseEntity']);
        $instance->expects($this->once())
                 ->method('getChunkedExportEntities')
                 ->with($this->identicalTo($exportData), $this->identicalTo($offset), $this->identicalTo($limit))
                 ->willReturn($exportEntities);
        $instance->expects($this->exactly(2))
                 ->method('createDatabaseEntity')
                 ->withConsecutive(
                     [$this->identicalTo($exportEntity1)],
                     [$this->identicalTo($exportEntity2)],
                 )
                 ->willReturnOnConsecutiveCalls(
                     $databaseEntity1,
                     $databaseEntity2,
                 );

        $result = $this->invokeMethod($instance, 'getDatabaseEntities', $exportData, $offset, $limit);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
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

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'fetchExistingEntities', $entities);

        $this->assertEquals($expectedResult, $result);
    }

    public function testCleanup(): void
    {
        $this->repository = $this->createMock(AbstractIdRepositoryWithOrphans::class);
        $this->repository->expects($this->once())
                         ->method('removeOrphans');

        $instance = $this->createInstance();
        $instance->cleanup();
    }
}
