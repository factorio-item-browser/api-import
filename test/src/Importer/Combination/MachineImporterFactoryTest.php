<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Machine;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Import\Database\CraftingCategoryService;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\MachineImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\MachineImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the MachineImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Combination\MachineImporterFactory
 */
class MachineImporterFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        /* @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
                              ->setMethods(['getRepository'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $entityManager->expects($this->once())
                      ->method('getRepository')
                      ->with(Machine::class)
                      ->willReturn($this->createMock(MachineRepository::class));

        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->exactly(3))
                  ->method('get')
                  ->withConsecutive(
                      [CraftingCategoryService::class],
                      [EntityManager::class],
                      [RegistryService::class]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $this->createMock(CraftingCategoryService::class),
                      $entityManager,
                      $this->createMock(RegistryService::class)
                  );

        $factory = new MachineImporterFactory();
        $factory($container, MachineImporter::class);
    }
}
