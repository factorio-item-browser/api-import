<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Exception;

use Exception;
use FactorioItemBrowser\Api\Import\Exception\UnknownImportPartException;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the UnknownImportPartException class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Exception\UnknownImportPartException
 */
class UnknownImportPartExceptionTest extends TestCase
{
    /**
     * Tests the constructing.
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $name = 'abc';
        $expectedMessage = 'Unknown part to import: abc';

        $previous = $this->createMock(Exception::class);

        $exception = new UnknownImportPartException($name, $previous);

        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
