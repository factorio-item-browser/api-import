<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Middleware;

use FactorioItemBrowser\Api\Import\Middleware\ApiKeyMiddleware;
use FactorioItemBrowser\Api\Import\Middleware\ApiKeyMiddlewareFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the ApiKeyMiddlewareFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Middleware\ApiKeyMiddlewareFactory
 */
class ApiKeyMiddlewareFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        $config = [
            'factorio-item-browser' => [
                'api-import' => [
                    'api-keys' => [
                        'foo' => 'bar',
                    ],
                ],
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



        $factory = new ApiKeyMiddlewareFactory();
        $factory($container, ApiKeyMiddleware::class);
    }
}
