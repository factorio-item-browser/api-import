<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Repository\RepositoryWithOrphansInterface;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\Generic\CleanupImporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the CleanupImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\CleanupImporter
 */
class CleanupImporterTest extends TestCase
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
        $repositories = [
            $this->createMock(RepositoryWithOrphansInterface::class),
            $this->createMock(RepositoryWithOrphansInterface::class),
        ];
        
        $importer = new CleanupImporter($entityManager, $repositories);
        
        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($repositories, $this->extractProperty($importer, 'repositoriesWithOrphans'));
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        /* @var RepositoryWithOrphansInterface|MockObject $repository1 */
        $repository1 = $this->getMockBuilder(RepositoryWithOrphansInterface::class)
                            ->setMethods(['removeOrphans'])
                            ->getMockForAbstractClass();
        $repository1->expects($this->once())
                    ->method('removeOrphans');
        
        /* @var RepositoryWithOrphansInterface|MockObject $repository2 */
        $repository2 = $this->getMockBuilder(RepositoryWithOrphansInterface::class)
                            ->setMethods(['removeOrphans'])
                            ->getMockForAbstractClass();
        $repository2->expects($this->once())
                    ->method('removeOrphans');

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        /* @var CleanupImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CleanupImporter::class)
                         ->setMethods(['flushEntities'])
                         ->setConstructorArgs([$entityManager, [$repository1, $repository2]])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('flushEntities');

        $importer->import();
    }
}
