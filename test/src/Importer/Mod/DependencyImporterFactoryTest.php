<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Import\Database\ModService;
use FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporter;
use FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the DependencyImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporterFactory
 */
class DependencyImporterFactoryTest extends TestCase
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
        $container->expects($this->exactly(2))
                  ->method('get')
                  ->withConsecutive(
                      [EntityManagerInterface::class],
                      [ModService::class]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $this->createMock(EntityManagerInterface::class),
                      $this->createMock(ModService::class)
                  );

        $factory = new DependencyImporterFactory();
        $factory($container, DependencyImporter::class);
    }
}
