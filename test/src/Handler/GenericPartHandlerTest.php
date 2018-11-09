<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Handler;

use BluePsyduck\Common\Test\ReflectionTrait;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Handler\GenericPartHandler;
use FactorioItemBrowser\Api\Import\Importer\Generic\GenericImporterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * The PHPUnit test of the GenericPartHandler class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Handler\GenericPartHandler
 */
class GenericPartHandlerTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var GenericImporterInterface $importer */
        $importer = $this->createMock(GenericImporterInterface::class);

        $handler = new GenericPartHandler($importer);

        $this->assertSame($importer, $this->extractProperty($handler, 'importer'));
    }

    /**
     * Tests the handle method.
     * @throws ImportException
     * @covers ::handle
     */
    public function testHandle(): void
    {
        /* @var GenericImporterInterface|MockObject $importer */
        $importer = $this->getMockBuilder(GenericImporterInterface::class)
                         ->setMethods(['import'])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('import');

        /* @var ServerRequestInterface $request */
        $request = $this->createMock(ServerRequestInterface::class);

        $handler = new GenericPartHandler($importer);
        $result = $handler->handle($request);

        $this->assertInstanceOf(EmptyResponse::class, $result);
    }
}
