<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModDependency as DatabaseDependency;
use FactorioItemBrowser\Api\Import\Database\ModService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the DependencyImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporter
 */
class DependencyImporterTest extends TestCase
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
        /* @var ModService $modService */
        $modService = $this->createMock(ModService::class);

        $importer = new DependencyImporter($entityManager, $modService);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($modService, $this->extractProperty($importer, 'modService'));
    }
    
    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        $newDependencies = [
            $this->createMock(DatabaseDependency::class),
            $this->createMock(DatabaseDependency::class),
        ];
        $existingDependencies = [
            $this->createMock(DatabaseDependency::class),
            $this->createMock(DatabaseDependency::class),
        ];
        $persistedDependencies = [
            $this->createMock(DatabaseDependency::class),
            $this->createMock(DatabaseDependency::class),
        ];

        /* @var ExportMod $exportMod */
        $exportMod = $this->createMock(ExportMod::class);
        /* @var Collection $dependencyCollection */
        $dependencyCollection = $this->createMock(Collection::class);

        /* @var DatabaseMod|MockObject $databaseMod */
        $databaseMod = $this->getMockBuilder(DatabaseMod::class)
                            ->setMethods(['getDependencies'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $databaseMod->expects($this->once())
                    ->method('getDependencies')
                    ->willReturn($dependencyCollection);

        /* @var DependencyImporter|MockObject $importer */
        $importer = $this->getMockBuilder(DependencyImporter::class)
                         ->setMethods([
                             'getDependenciesFromMod',
                             'getExistingDependencies',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getDependenciesFromMod')
                 ->with($exportMod)
                 ->willReturn($newDependencies);
        $importer->expects($this->once())
                 ->method('getExistingDependencies')
                 ->with($newDependencies, $databaseMod)
                 ->willReturn($existingDependencies);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newDependencies, $existingDependencies)
                 ->willReturn($persistedDependencies);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedDependencies, $dependencyCollection);

        $importer->import($exportMod, $databaseMod);
    }
}
