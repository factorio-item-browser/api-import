<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Exception;

use BluePsyduck\TestHelper\ReflectionTrait;
use Exception;
use FactorioItemBrowser\Api\Import\Exception\CommandFailureException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the CommandFailureException class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Exception\CommandFailureException
 */
class CommandFailureExceptionTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $commandOutput = 'abc';
        $message = 'def';

        /* @var Exception&MockObject $previous */
        $previous = $this->createMock(Exception::class);

        /* @var CommandFailureException&MockObject $exception */
        $exception = $this->getMockBuilder(CommandFailureException::class)
                          ->onlyMethods(['extractExceptionMessage'])
                          ->disableOriginalConstructor()
                          ->getMock();
        $exception->expects($this->once())
                  ->method('extractExceptionMessage')
                  ->with($this->identicalTo($commandOutput))
                  ->willReturn($message);

        $exception->__construct($commandOutput, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Provides the data for the extractExceptionMessage test.
     * @return array<mixed>
     */
    public function provideExtractExceptionMessage(): array
    {
        $commandOutput1 = <<<EOT
Some messages.
Some more messages.
\e[37;41;1m                      
Actual error message.
\e[39;49;22m
------------------------
Some final messages.
EOT;

        $commandOutput2 = <<<EOT
Some messages.
Some more messages.
\e[37;41;1m
Actual multi-line
error message.
\e[39;49;22m
------------------------
Some final messages.
EOT;

        return [
            [$commandOutput1, 'Actual error message.'],
            [$commandOutput2, "Actual multi-line\nerror message."],
            ['Some useless text-', ''],
        ];
    }

    /**
     * Tests the extractExceptionMessage method.
     * @param string $commandOutput
     * @param string $expectedResult
     * @throws ReflectionException
     * @covers ::extractExceptionMessage
     * @dataProvider provideExtractExceptionMessage
     */
    public function testExtractExceptionMessage(string $commandOutput, string $expectedResult): void
    {
        /* @var CommandFailureException&MockObject $exception */
        $exception = $this->getMockBuilder(CommandFailureException::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $result = $this->invokeMethod($exception, 'extractExceptionMessage', $commandOutput);

        $this->assertSame($expectedResult, $result);
    }
}
