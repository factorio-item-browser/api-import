<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Import\Database\ModService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Mod\CombinationImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the CombinationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Mod\CombinationImporter
 */
class CombinationImporterTest extends TestCase
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
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new CombinationImporter($entityManager, $modService, $registryService);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($modService, $this->extractProperty($importer, 'modService'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
    }
    
    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        $newCombinations = [
            $this->createMock(DatabaseCombination::class),
            $this->createMock(DatabaseCombination::class),
        ];
        $existingCombinations = [
            $this->createMock(DatabaseCombination::class),
            $this->createMock(DatabaseCombination::class),
        ];
        $persistedCombinations = [
            $this->createMock(DatabaseCombination::class),
            $this->createMock(DatabaseCombination::class),
        ];

        /* @var ExportMod $exportMod */
        $exportMod = $this->createMock(ExportMod::class);
        /* @var Collection $combinationCollection */
        $combinationCollection = $this->createMock(Collection::class);

        /* @var DatabaseMod|MockObject $databaseMod */
        $databaseMod = $this->getMockBuilder(DatabaseMod::class)
                            ->setMethods(['getCombinations'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $databaseMod->expects($this->once())
                    ->method('getCombinations')
                    ->willReturn($combinationCollection);

        /* @var CombinationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CombinationImporter::class)
                         ->setMethods([
                             'getCombinationsFromMod',
                             'getExistingCombinations',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getCombinationsFromMod')
                 ->with($exportMod)
                 ->willReturn($newCombinations);
        $importer->expects($this->once())
                 ->method('getExistingCombinations')
                 ->with($newCombinations, $databaseMod)
                 ->willReturn($existingCombinations);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newCombinations, $existingCombinations)
                 ->willReturn($persistedCombinations);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedCombinations, $combinationCollection);

        $importer->import($exportMod, $databaseMod);
    }
}
