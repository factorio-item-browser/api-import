<?php

namespace FactorioItemBrowserTest\Api\Import\Middleware;

use BluePsyduck\Common\Test\ReflectionTrait;
use FactorioItemBrowser\Api\Import\Exception\ErrorResponseException;
use FactorioItemBrowser\Api\Import\Middleware\ApiKeyMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;

/**
 * The PHPUnit test of the ApiKeyMiddleware class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Middleware\ApiKeyMiddleware
 */
class ApiKeyMiddlewareTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $apiKeys = [
            'abc' => 'def',
            'ghi' => 'jkl',
        ];

        $middleware = new ApiKeyMiddleware($apiKeys);

        $this->assertSame($apiKeys, $this->extractProperty($middleware, 'apiKeys'));
    }

    /**
     * Provides the data for the process test.
     * @return array
     */
    public function provideProcess(): array
    {
        $apiKeys = [
            'abc' => 'def',
            'ghi' => 'jkl',
        ];

        return [
            [$apiKeys, 'def', false],
            [$apiKeys, 'foo', true],
            [$apiKeys, '', true],
        ];
    }

    /**
     * Tests the process method.
     * @param array $apiKeys
     * @param string $header
     * @param bool $expectException
     * @throws ErrorResponseException
     * @covers ::process
     * @dataProvider provideProcess
     */
    public function testProcess(array $apiKeys, string $header, bool $expectException): void
    {
        /* @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
                        ->setMethods(['getHeaderLine'])
                        ->getMockForAbstractClass();
        $request->expects($this->once())
                ->method('getHeaderLine')
                ->with('X-Api-Key')
                ->willReturn($header);

        /* @var ResponseInterface $response */
        $response = $this->createMock(ResponseInterface::class);

        /* @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)
                        ->setMethods(['handle'])
                        ->getMockForAbstractClass();
        $handler->expects($expectException ? $this->never() : $this->once())
                ->method('handle')
                ->with($request)
                ->willReturn($response);

        if ($expectException) {
            $this->expectException(ErrorResponseException::class);
            $this->expectExceptionCode(401);
        }

        $middleware = new ApiKeyMiddleware($apiKeys);
        $result = $middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }
}
