<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Import\Database\ModService;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Mod\CombinationImporter;
use FactorioItemBrowser\Api\Import\Importer\Mod\CombinationImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the CombinationImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Mod\CombinationImporterFactory
 */
class CombinationImporterFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->exactly(3))
                  ->method('get')
                  ->withConsecutive(
                      [EntityManager::class],
                      [ModService::class],
                      [RegistryService::class]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $this->createMock(EntityManager::class),
                      $this->createMock(ModService::class),
                      $this->createMock(RegistryService::class)
                  );

        $factory = new CombinationImporterFactory();
        $factory($container, CombinationImporter::class);
    }
}
