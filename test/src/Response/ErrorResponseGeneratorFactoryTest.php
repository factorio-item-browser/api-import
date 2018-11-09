<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Response;

use BluePsyduck\Common\Test\ReflectionTrait;
use FactorioItemBrowser\Api\Import\Constant\ServiceName;
use FactorioItemBrowser\Api\Import\Response\ErrorResponseGenerator;
use FactorioItemBrowser\Api\Import\Response\ErrorResponseGeneratorFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zend\Log\LoggerInterface;

/**
 * The PHPUnit test of the ErrorResponseGeneratorFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Response\ErrorResponseGeneratorFactory
 */
class ErrorResponseGeneratorFactoryTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Provides the data for the invoke test.
     * @return array
     */
    public function provideInvoke(): array
    {
        return [
            [true, $this->createMock(LoggerInterface::class)],
            [false, null]
        ];
    }

    /**
     * Tests the invoking.
     * @param bool $resultHas
     * @param mixed $logger
     * @throws ReflectionException
     * @covers ::__invoke
     * @dataProvider provideInvoke
     */
    public function testInvoke(bool $resultHas, $logger): void
    {
        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['has', 'get'])
                          ->disableOriginalConstructor()
                          ->getMock();
        $container->expects($this->once())
                  ->method('has')
                  ->with(ServiceName::LOGGER)
                  ->willReturn($resultHas);
        $container->expects($logger === null ? $this->never() : $this->once())
                  ->method('get')
                  ->with(ServiceName::LOGGER)
                  ->willReturn($logger);

        $factory = new ErrorResponseGeneratorFactory();
        $result = $factory($container, ErrorResponseGenerator::class);
        $this->assertSame($logger, $this->extractProperty($result, 'logger'));
    }
}
