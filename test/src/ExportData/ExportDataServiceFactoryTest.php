<?php

namespace FactorioItemBrowserTest\Api\Import\ExportData;

use FactorioItemBrowser\Api\Import\ExportData\ExportDataServiceFactory;
use FactorioItemBrowser\ExportData\Service\ExportDataService;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the ExportDataServiceFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\ExportData\ExportDataServiceFactory
 */
class ExportDataServiceFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        $config = [
            'export-data' => [
                'directory' => 'abc',
            ],
        ];

        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->once())
                  ->method('get')
                  ->with('config')
                  ->willReturn($config);

        $factory = new ExportDataServiceFactory();
        $factory($container, ExportDataService::class);
    }
}
