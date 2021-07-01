<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Import\Command\CleanCommand;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The PHPUnit test of the CleanCommand class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Command\CleanCommand
 */
class CleanCommandTest extends TestCase
{
    use ReflectionTrait;

    /** @var Console&MockObject */
    private Console $console;
    /** @var array<string, ImporterInterface> */
    private array $importers = [];

    protected function setUp(): void
    {
        $this->console = $this->createMock(Console::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return CleanCommand&MockObject
     */
    private function createInstance(array $mockedMethods = []): CleanCommand
    {
        return $this->getMockBuilder(CleanCommand::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->console,
                        $this->importers,
                    ])
                    ->getMock();
    }

    /**
     * @throws ReflectionException
     */
    public function testConfigure(): void
    {
        $instance = $this->createInstance(['setName', 'setDescription']);

        $instance->expects($this->once())
                 ->method('setName')
                 ->with($this->identicalTo(CommandName::CLEAN));
        $instance->expects($this->once())
                 ->method('setDescription')
                 ->with($this->isType('string'));

        $this->invokeMethod($instance, 'configure');
    }

    /**
     * @throws ReflectionException
     */
    public function testExecute(): void
    {
        $importer1 = $this->createMock(ImporterInterface::class);
        $importer1->expects($this->once())
                  ->method('cleanup');

        $importer2 = $this->createMock(ImporterInterface::class);
        $importer2->expects($this->once())
                  ->method('cleanup');

        $this->importers = [
            'abc' => $importer1,
            'def' => $importer2,
        ];

        $this->console->expects($this->exactly(2))
                      ->method('writeAction')
                      ->withConsecutive(
                          [$this->identicalTo('Importer: def')],
                          [$this->identicalTo('Importer: abc')],
                      );

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $instance = $this->createInstance();

        $this->invokeMethod($instance, 'execute', $input, $output);
    }
}
