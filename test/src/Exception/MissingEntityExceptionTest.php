<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Exception;

use Exception;
use FactorioItemBrowser\Api\Import\Exception\MissingEntityException;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the MissingEntityException class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Exception\MissingEntityException
 */
class MissingEntityExceptionTest extends TestCase
{
    /**
     * Tests the constructing.
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $entityClass = self::class;
        $identifier = 'abc';
        $previous = new Exception('foo');
        $expectedMessage = 'Missing MissingEntityExceptionTest: abc';

        $exception = new MissingEntityException($entityClass, $identifier, $previous);

        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
