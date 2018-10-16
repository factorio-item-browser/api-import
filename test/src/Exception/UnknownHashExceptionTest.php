<?php

namespace FactorioItemBrowserTest\Api\Import\Exception;

use Exception;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the UnknownHashException class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Exception\UnknownHashException
 */
class UnknownHashExceptionTest extends TestCase
{
    /**
     * Tests the constructing.
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $entityType = 'abc';
        $hash = 'def';
        $previous = new Exception('ghi');
        $expectedMessage = 'Unable to find abc with hash def.';

        $exception = new UnknownHashException($entityType, $hash, $previous);

        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
