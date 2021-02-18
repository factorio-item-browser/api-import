<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Console;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The PHPUnit test of the Console class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Console\Console
 */
class ConsoleTest extends TestCase
{
    use ReflectionTrait;

    /** @var OutputInterface&MockObject */
    private OutputInterface $output;
    private bool $isDebug = false;

    protected function setUp(): void
    {
        $this->output = $this->createMock(OutputInterface::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return Console&MockObject
     */
    private function createInstance(array $mockedMethods = []): Console
    {
        return $this->getMockBuilder(Console::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([$this->output, $this->isDebug])
                    ->getMock();
    }

    public function testWriteHeadline(): void
    {
        $message = 'abc';

        $this->output->expects($this->once())
                     ->method('writeln')
                     ->with($this->stringContains($message));

        $instance = $this->createInstance();
        $result = $instance->writeHeadline($message);

        $this->assertSame($instance, $result);
    }

    public function testWriteStep(): void
    {
        $step = 'abc';

        $this->output->expects($this->once())
                     ->method('writeln')
                     ->with($this->stringContains($step));

        $instance = $this->createInstance();
        $result = $instance->writeStep($step);

        $this->assertSame($instance, $result);
    }

    public function testWriteAction(): void
    {
        $action = 'abc';

        $this->output->expects($this->once())
                     ->method('writeln')
                     ->with($this->stringContains($action));

        $instance = $this->createInstance();
        $result = $instance->writeAction($action);

        $this->assertSame($instance, $result);
    }

    public function testWriteMessage(): void
    {
        $message = 'abc';

        $this->output->expects($this->once())
                     ->method('writeln')
                     ->with($this->stringContains($message));

        $instance = $this->createInstance();
        $result = $instance->writeMessage($message);

        $this->assertSame($instance, $result);
    }

    public function testWriteData(): void
    {
        $message = 'abc';

        $this->output->expects($this->once())
                     ->method('write')
                     ->with(
                         $this->equalTo($message),
                         $this->isFalse(),
                         $this->identicalTo(OutputInterface::OUTPUT_RAW),
                     );

        $instance = $this->createInstance();
        $result = $instance->writeData($message);

        $this->assertSame($instance, $result);
    }

    public function testWriteException(): void
    {
        $exception = new ImportException('abc');

        $this->output->expects($this->once())
                     ->method('writeln')
                     ->with($this->logicalAnd(
                         $this->stringContains('abc'),
                         $this->stringContains('ImportException'),
                     ));

        $this->isDebug = false;

        $instance = $this->createInstance();
        $result = $instance->writeException($exception);

        $this->assertSame($instance, $result);
    }

    public function testWriteExceptionWithDebug(): void
    {
        $exception = new ImportException('abc');

        $this->output->expects($this->exactly(2))
                     ->method('writeln')
                     ->withConsecutive(
                         [$this->logicalAnd(
                             $this->stringContains('abc'),
                             $this->stringContains('ImportException'),
                         )],
                         [$this->stringContains($exception->getTraceAsString())],
                     );

        $this->isDebug = true;

        $instance = $this->createInstance();
        $result = $instance->writeException($exception);

        $this->assertSame($instance, $result);
    }
}
