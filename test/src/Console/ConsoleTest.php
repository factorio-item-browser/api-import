<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Console;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zend\Console\Adapter\AdapterInterface;
use Zend\Console\ColorInterface;

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
     * The mocked console adapter.
     * @var AdapterInterface&MockObject
     */
    protected $consoleAdapter;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->consoleAdapter = $this->createMock(AdapterInterface::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $console = new Console($this->consoleAdapter, true);

        $this->assertSame($this->consoleAdapter, $this->extractProperty($console, 'consoleAdapter'));
        $this->assertTrue($this->extractProperty($console, 'isDebug'));
    }

    /**
     * Tests the writeHeadline method.
     * @covers ::writeHeadline
     */
    public function testWriteHeadline(): void
    {
        $this->consoleAdapter->expects($this->exactly(4))
                             ->method('writeLine')
                             ->withConsecutive(
                                 [],
                                 [$this->identicalTo('---'), $this->identicalTo(ColorInterface::LIGHT_YELLOW)],
                                 [$this->identicalTo(' abc'), $this->identicalTo(ColorInterface::LIGHT_YELLOW)],
                                 [$this->identicalTo('---'), $this->identicalTo(ColorInterface::LIGHT_YELLOW)]
                             );

        /* @var Console&MockObject $console */
        $console = $this->getMockBuilder(Console::class)
                        ->onlyMethods(['createHorizontalLine'])
                        ->setConstructorArgs([$this->consoleAdapter, true])
                        ->getMock();
        $console->expects($this->exactly(2))
                ->method('createHorizontalLine')
                ->with('-')
                ->willReturn('---');

        $result = $console->writeHeadline('abc');

        $this->assertSame($console, $result);
    }

    /**
     * Tests the writeStep method.
     * @covers ::writeStep
     */
    public function testWriteStep(): void
    {
        $this->consoleAdapter->expects($this->exactly(3))
                             ->method('writeLine')
                             ->withConsecutive(
                                 [],
                                 [$this->identicalTo(' abc'), $this->identicalTo(ColorInterface::LIGHT_BLUE)],
                                 [$this->identicalTo('---'), $this->identicalTo(ColorInterface::LIGHT_BLUE)]
                             );

        /* @var Console&MockObject $console */
        $console = $this->getMockBuilder(Console::class)
                        ->onlyMethods(['createHorizontalLine'])
                        ->setConstructorArgs([$this->consoleAdapter, true])
                        ->getMock();
        $console->expects($this->once())
                ->method('createHorizontalLine')
                ->with('-')
                ->willReturn('---');

        $result = $console->writeStep('abc');

        $this->assertSame($console, $result);
    }

    /**
     * Tests the writeAction method.
     * @covers ::writeAction
     */
    public function testWriteAction(): void
    {
        $this->consoleAdapter->expects($this->once())
                             ->method('writeLine')
                             ->with($this->identicalTo('> abc...'));

        $console = new Console($this->consoleAdapter, true);
        $result = $console->writeAction('abc');

        $this->assertSame($console, $result);
    }

    /**
     * Tests the writeMessage method.
     * @covers ::writeMessage
     */
    public function testWriteMessage(): void
    {
        $this->consoleAdapter->expects($this->once())
                             ->method('writeLine')
                             ->with($this->identicalTo('# abc'));

        $console = new Console($this->consoleAdapter, true);
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
        $expectedMessage = '! ImportException: abc';

        $this->consoleAdapter->expects($this->once())
                             ->method('writeLine')
                             ->with(
                                 $this->identicalTo($expectedMessage),
                                 $this->identicalTo(ColorInterface::LIGHT_RED)
                             );

        $console = new Console($this->consoleAdapter, false);
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
        $expectedMessage = '! ImportException: abc';

        $this->consoleAdapter->expects($this->exactly(4))
                             ->method('writeLine')
                             ->withConsecutive(
                                 [$this->identicalTo($expectedMessage), $this->identicalTo(ColorInterface::LIGHT_RED)],
                                 [$this->identicalTo('---'), $this->identicalTo(ColorInterface::RED)],
                                 [$this->isType('string'), $this->identicalTo(ColorInterface::RED)],
                                 [$this->identicalTo('---'), $this->identicalTo(ColorInterface::RED)]
                             );

        /* @var Console&MockObject $console */
        $console = $this->getMockBuilder(Console::class)
                        ->onlyMethods(['createHorizontalLine'])
                        ->setConstructorArgs([$this->consoleAdapter, true])
                        ->getMock();
        $console->expects($this->any())
                ->method('createHorizontalLine')
                ->with('-')
                ->willReturn('---');

        $result = $console->writeException($exception);

        $this->assertSame($console, $result);
    }


    /**
     * Tests the writeData method.
     * @covers ::writeData
     */
    public function testWriteData(): void
    {
        $this->consoleAdapter->expects($this->once())
                             ->method('write')
                             ->with($this->identicalTo('abc'));


        $console = new Console($this->consoleAdapter, true);
        $result = $console->writeData('abc');

        $this->assertSame($console, $result);
    }

    /**
     * Tests the createHorizontalLine method.
     * @throws ReflectionException
     * @covers ::createHorizontalLine
     */
    public function testCreateHorizontalLine(): void
    {
        $width = 16;
        $character = '-';
        $expectedResult = '----------------';

        $this->consoleAdapter->expects($this->once())
                             ->method('getWidth')
                             ->willReturn($width);

        $console = new Console($this->consoleAdapter, true);
        $result = $this->invokeMethod($console, 'createHorizontalLine', $character);

        $this->assertSame($expectedResult, $result);
    }
}
