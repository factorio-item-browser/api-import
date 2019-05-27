<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManagerInterface;
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
        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->exactly(3))
                  ->method('get')
                  ->withConsecutive(
                      [EntityManagerInterface::class],
                      [ModCombinationRepository::class],
                      [ModRepository::class]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $this->createMock(EntityManagerInterface::class),
                      $this->createMock(ModCombinationRepository::class),
                      $this->createMock(ModRepository::class)
                  );

        $factory = new CombinationOrderImporterFactory();
        $factory($container, CombinationOrderImporter::class);
    }
}
