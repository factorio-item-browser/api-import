<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Handler;

use FactorioItemBrowser\Api\Import\Constant\ServiceName;
use FactorioItemBrowser\Api\Import\Handler\AbstractGenericPartHandlerFactory;
use FactorioItemBrowser\Api\Import\Importer\Generic\CleanupImporter;
use FactorioItemBrowser\Api\Import\Importer\Generic\CombinationOrderImporter;
use FactorioItemBrowser\Api\Import\Importer\Generic\GenericImporterInterface;
use FactorioItemBrowser\Api\Import\Importer\Generic\ModOrderImporter;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the AbstractGenericPartHandlerFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Handler\AbstractGenericPartHandlerFactory
 */
class AbstractGenericPartHandlerFactoryTest extends TestCase
{
    /**
     * Provides the data for the canCreate test.
     * @return array
     */
    public function provideCanCreate(): array
    {
        return [
            [ServiceName::GENERIC_CLEANUP, true],
            [ServiceName::GENERIC_CLEAR_CACHE, true],
            [ServiceName::GENERIC_ORDER_COMBINATIONS, true],
            [ServiceName::GENERIC_ORDER_MODS, true],
            ['foo', false],
        ];
    }

    /**
     * Tests the canCreate method.
     * @param string $requestedName
     * @param bool $expectedResult
     * @covers ::canCreate
     * @dataProvider provideCanCreate
     */
    public function testCanCreate(string $requestedName, bool $expectedResult): void
    {
        /* @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $factory = new AbstractGenericPartHandlerFactory();
        $result = $factory->canCreate($container, $requestedName);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the invoke test.
     * @return array
     */
    public function provideInvoke(): array
    {
        return [
            [ServiceName::GENERIC_CLEANUP, CleanupImporter::class],
            [ServiceName::GENERIC_ORDER_COMBINATIONS, CombinationOrderImporter::class],
            [ServiceName::GENERIC_ORDER_MODS, ModOrderImporter::class],
        ];
    }

    /**
     * Tests the invoking.
     * @param string $requestedName
     * @param string $expectedImporterClass
     * @covers ::__invoke
     * @dataProvider provideInvoke
     */
    public function testInvoke(string $requestedName, string $expectedImporterClass): void
    {
        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->once())
                  ->method('get')
                  ->with($expectedImporterClass)
                  ->willReturn($this->createMock(GenericImporterInterface::class));

        $factory = new AbstractGenericPartHandlerFactory();
        $factory($container, $requestedName);
    }
}
