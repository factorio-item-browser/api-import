<?php

namespace FactorioItemBrowserTest\Api\Import\ExportData;

use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\ExportData\RegistryServiceFactory;
use FactorioItemBrowser\ExportData\Service\ExportDataService;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the RegistryServiceFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\ExportData\RegistryServiceFactory
 */
class RegistryServiceFactoryTest extends TestCase
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
        $container->expects($this->once())
                  ->method('get')
                  ->with(ExportDataService::class)
                  ->willReturn($this->createMock(ExportDataService::class));

        $factory = new RegistryServiceFactory();
        $factory($container, RegistryService::class);
    }
}
