<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Repository\ModCombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Importer\Generic\CombinationOrderImporter;
use FactorioItemBrowser\Api\Import\Importer\Generic\CombinationOrderImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the CombinationOrderImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\CombinationOrderImporterFactory
 */
class CombinationOrderImporterFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);
        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);

        /* @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
                              ->setMethods(['getRepository'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $entityManager->expects($this->exactly(2))
                      ->method('getRepository')
                      ->withConsecutive(
                          [ModCombination::class],
                          [Mod::class]
                      )
                      ->willReturnOnConsecutiveCalls(
                          $modCombinationRepository,
                          $modRepository
                      );

        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->once())
                  ->method('get')
                  ->with(EntityManager::class)
                  ->willReturn($entityManager);

        $factory = new CombinationOrderImporterFactory();
        $factory($container, CombinationOrderImporter::class);
    }
}
