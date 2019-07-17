<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Response;

use BluePsyduck\TestHelper\ReflectionTrait;
use Exception;
use FactorioItemBrowser\Api\Import\Response\ErrorResponseGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionException;
use Zend\Log\LoggerInterface;

/**
 * The PHPUnit test of the ErrorResponseGenerator class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Response\ErrorResponseGenerator
 */
class ErrorResponseGeneratorTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @covers ::__construct
     * @throws ReflectionException
     */
    public function testConstruct(): void
    {
        /* @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $generator = new ErrorResponseGenerator($logger);

        $this->assertSame($logger, $this->extractProperty($generator, 'logger'));
    }

    /**
     * Provides the data for the invoke test.
     * @return array
     */
    public function provideInvoke(): array
    {
        return [
            [400, 400, false, false],
            [400, 400, true, false],
            [0, 500, false, false],
            [0, 500, true, true]
        ];
    }

    /**
     * Tests the invoking.
     * @param int $exceptionCode
     * @param bool $withLogger
     * @param int $expectedStatusCode
     * @throws ReflectionException
     * @covers ::__invoke
     * @dataProvider provideInvoke
     */
    public function testInvoke(int $exceptionCode, int $expectedStatusCode, bool $withLogger, bool $expectLogger): void
    {
        $message = 'abc';
        $exception = new Exception($message, $exceptionCode);

        $logger = null;
        if ($withLogger) {
            /* @var LoggerInterface|MockObject $logger */
            $logger = $this->getMockBuilder(LoggerInterface::class)
                           ->setMethods(['crit'])
                           ->getMockForAbstractClass();
            $logger->expects($expectLogger ? $this->once() : $this->never())
                   ->method('crit')
                   ->with($exception);
        }

        /* @var ServerRequestInterface $request */
        $request = $this->createMock(ServerRequestInterface::class);
        /* @var ResponseInterface $response */
        $response = $this->createMock(ResponseInterface::class);

        $generator = new ErrorResponseGenerator($logger);
        $result = $generator($exception, $request, $response);

        $this->assertSame($expectedStatusCode, $result->getStatusCode());
        $this->assertSame($message, $result->getBody()->getContents());
    }

    /**
     * Tests the createResponseStream method.
     * @throws ReflectionException
     * @covers ::createResponseStream
     */
    public function testCreateResponseStream(): void
    {
        $contents = 'abc';

        $generator = new ErrorResponseGenerator(null);
        $result = $this->invokeMethod($generator, 'createResponseStream', $contents);
        /* @var StreamInterface $result */

        $this->assertSame($contents, $result->getContents());
    }
}
