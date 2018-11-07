<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Generic\ModOrderImporter;
use FactorioItemBrowser\Api\Import\Importer\Generic\ModOrderImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the ModOrderImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\ModOrderImporterFactory
 */
class ModOrderImporterFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        /* @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
                              ->setMethods(['getRepository'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $entityManager->expects($this->once())
                      ->method('getRepository')
                      ->with(Mod::class)
                      ->willReturn($modRepository);

        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->exactly(2))
                  ->method('get')
                  ->withConsecutive(
                      [EntityManager::class],
                      [RegistryService::class]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $entityManager,
                      $this->createMock(RegistryService::class)
                  );

        $factory = new ModOrderImporterFactory();
        $factory($container, ModOrderImporter::class);
    }
}
