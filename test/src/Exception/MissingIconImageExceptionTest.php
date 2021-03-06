<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Exception;

use Exception;
use FactorioItemBrowser\Api\Import\Exception\MissingIconImageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the MissingIconImageException class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Exception\MissingIconImageException
 */
class MissingIconImageExceptionTest extends TestCase
{
    /**
     * Tests the constructing.
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $imageId = 'abc';
        $expectedMessage = 'Expected icon image abc, but it was not there.';

        /* @var Exception&MockObject $previous */
        $previous = $this->createMock(Exception::class);

        $exception = new MissingIconImageException($imageId, $previous);

        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
