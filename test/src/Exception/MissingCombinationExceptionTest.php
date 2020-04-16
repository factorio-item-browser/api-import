<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Exception;

use Exception;
use FactorioItemBrowser\Api\Import\Exception\MissingCombinationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * The PHPUnit test of the MissingCombinationException class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Exception\MissingCombinationException
 */
class MissingCombinationExceptionTest extends TestCase
{
    /**
     * Tests the constructing.
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $combinationId = Uuid::fromString('dceaf73a-1bca-4514-a53f-edc876ba8a05');
        $expectedMessage = 'Expected combination dceaf73a-1bca-4514-a53f-edc876ba8a05, but it was not there.';

        /* @var Exception&MockObject $previous */
        $previous = $this->createMock(Exception::class);

        $exception = new MissingCombinationException($combinationId, $previous);

        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
