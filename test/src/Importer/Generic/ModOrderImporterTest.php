<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Generic\ModOrderImporter;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the ModOrderImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\ModOrderImporter
 */
class ModOrderImporterTest extends TestCase
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
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new ModOrderImporter($entityManager, $modRepository, $registryService);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($modRepository, $this->extractProperty($importer, 'modRepository'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
    }
}
