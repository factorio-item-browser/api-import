<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Exception;

use Exception;
use FactorioItemBrowser\Api\Import\Exception\MissingCraftingCategoryException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the MissingCraftingCategoryException class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Exception\MissingCraftingCategoryException
 */
class MissingCraftingCategoryExceptionTest extends TestCase
{
    /**
     * Tests the constructing.
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $name = 'abc';
        $expectedMessage = 'Expected crafting category abc, but it was not there.';

        /* @var Exception&MockObject $previous */
        $previous = $this->createMock(Exception::class);

        $exception = new MissingCraftingCategoryException($name, $previous);

        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
