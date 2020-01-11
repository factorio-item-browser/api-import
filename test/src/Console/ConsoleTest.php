<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Console;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The PHPUnit test of the Console class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Console\Console
 */
class ConsoleTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked output.
     * @var OutputInterface&MockObject
     */
    protected $output;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->output = $this->createMock(OutputInterface::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $console = new Console($this->output, true);

        $this->assertSame($this->output, $this->extractProperty($console, 'output'));
        $this->assertTrue($this->extractProperty($console, 'isDebug'));
    }

    /**
     * Tests the writeHeadline method.
     * @covers ::writeHeadline
     */
    public function testWriteHeadline(): void
    {
        $message = 'abc';
        $expectedMessages = ['', '---', ' abc', '---'];

        /* @var Console&MockObject $console */
        $console = $this->getMockBuilder(Console::class)
                        ->onlyMethods(['createHorizontalLine', 'writeWithDecoration'])
                        ->setConstructorArgs([$this->output, true])
                        ->getMock();
        $console->expects($this->exactly(2))
                ->method('createHorizontalLine')
                ->with('-')
                ->willReturn('---');
        $console->expects($this->once())
                ->method('writeWithDecoration')
                ->with($this->identicalTo($expectedMessages), $this->identicalTo('yellow'), $this->identicalTo('bold'));

        $result = $console->writeHeadline($message);

        $this->assertSame($console, $result);
    }

    /**
     * Tests the writeStep method.
     * @covers ::writeStep
     */
    public function testWriteStep(): void
    {
        $step = 'abc';
        $expectedMessages = ['', ' abc', '---'];

        /* @var Console&MockObject $console */
        $console = $this->getMockBuilder(Console::class)
                        ->onlyMethods(['createHorizontalLine', 'writeWithDecoration'])
                        ->setConstructorArgs([$this->output, true])
                        ->getMock();
        $console->expects($this->once())
                ->method('createHorizontalLine')
                ->with('-')
                ->willReturn('---');
        $console->expects($this->once())
                ->method('writeWithDecoration')
                ->with($this->identicalTo($expectedMessages), $this->identicalTo('blue'), $this->identicalTo('bold'));

        $result = $console->writeStep($step);

        $this->assertSame($console, $result);
    }

    /**
     * Tests the writeAction method.
     * @covers ::writeAction
     */
    public function testWriteAction(): void
    {
        $this->output->expects($this->once())
                     ->method('writeln')
                     ->with($this->identicalTo('> abc...'));

        $console = new Console($this->output, true);
        $result = $console->writeAction('abc');

        $this->assertSame($console, $result);
    }

    /**
     * Tests the writeMessage method.
     * @covers ::writeMessage
     */
    public function testWriteMessage(): void
    {
        $this->output->expects($this->once())
                     ->method('writeln')
                     ->with($this->identicalTo('# abc'));

        $console = new Console($this->output, true);
        $result = $console->writeMessage('abc');

        $this->assertSame($console, $result);
    }

    /**
     * Tests the writeException method.
     * @covers ::writeException
     */
    public function testWriteException(): void
    {
        $exception = new ImportException('abc');
        $expectedMessages = ['! ImportException: abc'];

        /* @var Console&MockObject $console */
        $console = $this->getMockBuilder(Console::class)
                        ->onlyMethods(['createHorizontalLine', 'writeWithDecoration'])
                        ->setConstructorArgs([$this->output, false])
                        ->getMock();
        $console->expects($this->never())
                ->method('createHorizontalLine');
        $console->expects($this->once())
                ->method('writeWithDecoration')
                ->with($this->identicalTo($expectedMessages), $this->identicalTo('red'), $this->identicalTo('bold'));

        $result = $console->writeException($exception);

        $this->assertSame($console, $result);
    }

    /**
     * Tests the writeException method.
     * @covers ::writeException
     */
    public function testWriteExceptionWithDebug(): void
    {
        $exception = new ImportException('abc');
        $expectedMessages1 = ['! ImportException: abc'];
        $expectedMessages2 = ['---', $exception->getTraceAsString(), '---'];

        /* @var Console&MockObject $console */
        $console = $this->getMockBuilder(Console::class)
                        ->onlyMethods(['createHorizontalLine', 'writeWithDecoration'])
                        ->setConstructorArgs([$this->output, true])
                        ->getMock();
        $console->expects($this->exactly(2))
                ->method('createHorizontalLine')
                ->with('-')
                ->willReturn('---');
        $console->expects($this->exactly(2))
                ->method('writeWithDecoration')
                ->withConsecutive(
                    [$this->identicalTo($expectedMessages1), $this->identicalTo('red'), $this->identicalTo('bold')],
                    [$this->identicalTo($expectedMessages2), $this->identicalTo('red'), $this->identicalTo('')]
                );

        $result = $console->writeException($exception);

        $this->assertSame($console, $result);
    }

    /**
     * Tests the writeData method.
     * @covers ::writeData
     */
    public function testWriteData(): void
    {
        $this->output->expects($this->once())
                     ->method('write')
                     ->with($this->identicalTo('abc'), $this->isFalse(), $this->identicalTo(ConsoleOutput::OUTPUT_RAW));

        $console = new Console($this->output, true);
        $result = $console->writeData('abc');

        $this->assertSame($console, $result);
    }

    /**
     * Provides the data for the writeWithDecoration test.
     * @return array<mixed>
     */
    public function provideWriteWithDecoration(): array
    {
        return [
            ['foo', 'bar', 'fg=foo;options=bar'],
            ['foo', '', 'fg=foo'],
            ['', 'bar', 'options=bar'],
        ];
    }

    /**
     * Tests the writeWithDecoration method.
     * @param string $color
     * @param string $options
     * @param string $expectedFormatString
     * @throws ReflectionException
     * @covers ::writeWithDecoration
     * @dataProvider provideWriteWithDecoration
     */
    public function testWriteWithDecoration(string $color, string $options, string $expectedFormatString): void
    {
        $messages = ['abc', 'def', 'ghi'];
        $expectedMessages = ["<{$expectedFormatString}>abc", 'def', 'ghi</>'];

        $this->output->expects($this->once())
                     ->method('writeln')
                     ->with($this->identicalTo($expectedMessages));

        $console = new Console($this->output, true);
        $this->invokeMethod($console, 'writeWithDecoration', $messages, $color, $options);
    }

    /**
     * Tests the writeWithDecoration method.
     * @throws ReflectionException
     * @covers ::writeWithDecoration
     */
    public function testWriteWithDecorationWithoutFormats(): void
    {
        $messages = ['abc', 'def', 'ghi'];

        $this->output->expects($this->once())
                     ->method('writeln')
                     ->with($this->identicalTo($messages));

        $console = new Console($this->output, true);
        $this->invokeMethod($console, 'writeWithDecoration', $messages);
    }

    /**
     * Tests the createHorizontalLine method.
     * @throws ReflectionException
     * @covers ::createHorizontalLine
     */
    public function testCreateHorizontalLine(): void
    {
        $character = '-';
        $expectedResult = '--------------------------------------------------------------------------------';

        $console = new Console($this->output, true);
        $result = $this->invokeMethod($console, 'createHorizontalLine', $character);

        $this->assertSame($expectedResult, $result);
    }
}
