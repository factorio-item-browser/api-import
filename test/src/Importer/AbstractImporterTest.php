<?php

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;

/**
 * The PHPUnit test of the AbstractImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\AbstractImporter
 */
class AbstractImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        /* @var AbstractImporter|MockObject $importer */
        $importer = $this->getMockBuilder(AbstractImporter::class)
                         ->setConstructorArgs([$entityManager])
                         ->getMockForAbstractClass();

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
    }

    /**
     * Tests the persistEntities method.
     * @throws ReflectionException
     * @covers ::persistEntities
     */
    public function testPersistEntities(): void
    {
        $entity1 = new stdClass();
        $entity1->foo = 'abc';
        $entity2 = new stdClass();
        $entity2->foo = 'def';
        $entity3 = new stdClass();
        $entity3->foo = 'ghi';
        $entity4 = new stdClass();
        $entity4->foo = 'jkl';

        $newEntities = [
            'abc' => $entity1,
            'def' => $entity2,
        ];
        $existingEntities = [
            'abc' => $entity3,
            'ghi' => $entity4
        ];
        $expectedResult = [
            'abc' => $entity3,
            'def' => $entity2,
        ];

        /* @var AbstractImporter|MockObject $importer */
        $importer = $this->getMockBuilder(AbstractImporter::class)
                         ->setMethods(['persistEntity', 'flushEntities'])
                         ->disableOriginalConstructor()
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('persistEntity')
                 ->with($entity2);
        $importer->expects($this->once())
                 ->method('flushEntities');

        $result = $this->invokeMethod($importer, 'persistEntities', $newEntities, $existingEntities);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Provides the data for the persistEntity test.
     * @return array
     */
    public function providePersistEntity(): array
    {
        return [
            [false, false],
            [true, true],
        ];
    }

    /**
     * Tests the persistEntity method.
     * @param bool $throwException
     * @param bool $expectException
     * @throws ReflectionException
     * @covers ::persistEntity
     * @dataProvider providePersistEntity
     */
    public function testPersistEntity(bool $throwException, bool $expectException): void
    {
        $entity = new stdClass();

        /* @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
                              ->setMethods(['persist'])
                              ->disableOriginalConstructor()
                              ->getMock();
        if ($throwException) {
            $entityManager->expects($this->once())
                          ->method('persist')
                          ->with($entity)
                          ->willThrowException($this->createMock(ORMException::class));
        } else {
            $entityManager->expects($this->once())
                          ->method('persist')
                          ->with($entity);
        }

        if ($expectException) {
            $this->expectException(ImportException::class);
        }

        /* @var AbstractImporter|MockObject $importer */
        $importer = $this->getMockBuilder(AbstractImporter::class)
                         ->setConstructorArgs([$entityManager])
                         ->getMockForAbstractClass();

        $this->invokeMethod($importer, 'persistEntity', $entity);
    }

    /**
     * Provides the data for the flushEntities test.
     * @return array
     */
    public function provideFlushEntities(): array
    {
        return [
            [false, false],
            [true, true],
        ];
    }

    /**
     * Tests the flushEntities method.
     * @param bool $throwException
     * @param bool $expectException
     * @throws ReflectionException
     * @covers ::flushEntities
     * @dataProvider provideFlushEntities
     */
    public function testFlushEntities(bool $throwException, bool $expectException): void
    {
        /* @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
                              ->setMethods(['flush'])
                              ->disableOriginalConstructor()
                              ->getMock();
        if ($throwException) {
            $entityManager->expects($this->once())
                          ->method('flush')
                          ->willThrowException($this->createMock(ORMException::class));
        } else {
            $entityManager->expects($this->once())
                          ->method('flush');
        }

        if ($expectException) {
            $this->expectException(ImportException::class);
        }

        /* @var AbstractImporter|MockObject $importer */
        $importer = $this->getMockBuilder(AbstractImporter::class)
                         ->setConstructorArgs([$entityManager])
                         ->getMockForAbstractClass();

        $this->invokeMethod($importer, 'flushEntities');
    }


    /**
     * Tests the assignEntitiesToCollection method.
     * @throws ReflectionException
     * @covers ::assignEntitiesToCollection
     */
    public function testAssignEntitiesToCollection(): void
    {
        $entity1 = new stdClass();
        $entity2 = new stdClass();

        /* @var Collection|MockObject $collection */
        $collection = $this->getMockBuilder(Collection::class)
                           ->setMethods(['clear', 'add'])
                           ->getMockForAbstractClass();
        $collection->expects($this->once())
                   ->method('clear');
        $collection->expects($this->exactly(2))
                   ->method('add')
                   ->withConsecutive(
                       [$entity1],
                       [$entity2]
                   );

        /* @var AbstractImporter|MockObject $importer */
        $importer = $this->getMockBuilder(AbstractImporter::class)
                         ->disableOriginalConstructor()
                         ->setMethods(['flushEntities'])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('flushEntities');

        $this->invokeMethod($importer, 'assignEntitiesToCollection', [$entity1, $entity2], $collection);
    }
}
