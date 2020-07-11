<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\SymfonyProcessManager\ProcessManager;
use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Command\ImportCommand;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Exception\CommandFailureException;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use FactorioItemBrowser\Api\Import\Process\ImportCommandProcess;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;

/**
 * The PHPUnit test of the ImportCommand class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Command\ImportCommand
 */
class ImportCommandTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked combination repository.
     * @var CombinationRepository&MockObject
     */
    protected $combinationRepository;

    /**
     * The mocked console.
     * @var Console&MockObject
     */
    protected $console;

    /**
     * The mocked export data service.
     * @var ExportDataService&MockObject
     */
    protected $exportDataService;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->combinationRepository = $this->createMock(CombinationRepository::class);
        $this->console = $this->createMock(Console::class);
        $this->exportDataService = $this->createMock(ExportDataService::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importers = [
            $this->createMock(ImporterInterface::class),
            $this->createMock(ImporterInterface::class),
        ];
        $chunkSize = 42;
        $numberOfParallelProcesses = 21;

        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $importers,
            $chunkSize,
            $numberOfParallelProcesses,
        );

        $this->assertSame($this->combinationRepository, $this->extractProperty($command, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($command, 'console'));
        $this->assertSame($this->exportDataService, $this->extractProperty($command, 'exportDataService'));
        $this->assertSame($importers, $this->extractProperty($command, 'importers'));
        $this->assertSame($chunkSize, $this->extractProperty($command, 'chunkSize'));
        $this->assertSame($numberOfParallelProcesses, $this->extractProperty($command, 'numberOfParallelProcesses'));
    }

    /**
     * Tests the configure method.
     * @throws ReflectionException
     * @covers ::configure
     */
    public function testConfigure(): void
    {
        $command = $this->getMockBuilder(ImportCommand::class)
                        ->onlyMethods(['setName', 'setDescription', 'addArgument'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->exportDataService,
                            [],
                            42,
                            21,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('setName')
                ->with($this->identicalTo(CommandName::IMPORT));
        $command->expects($this->once())
                ->method('setDescription')
                ->with($this->isType('string'));
        $command->expects($this->once())
                ->method('addArgument')
                ->with(
                    $this->identicalTo('combination'),
                    $this->identicalTo(InputArgument::REQUIRED),
                    $this->isType('string')
                );

        $this->invokeMethod($command, 'configure');
    }

    /**
     * Tests the import method.
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        $exportData = $this->createMock(ExportData::class);
        $combination = $this->createMock(Combination::class);

        $importer1 = $this->createMock(ImporterInterface::class);
        $importer2 = $this->createMock(ImporterInterface::class);

        $importers = [
            'abc' => $importer1,
            'def' => $importer2,
        ];

        $command = $this->getMockBuilder(ImportCommand::class)
                        ->onlyMethods(['executeImporter', 'cleanup'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->exportDataService,
                            $importers,
                            42,
                            21,
                        ])
                        ->getMock();
        $command->expects($this->exactly(2))
                ->method('executeImporter')
                ->withConsecutive(
                    [
                        $this->identicalTo('abc'),
                        $this->identicalTo($importer1),
                        $this->identicalTo($exportData),
                        $this->identicalTo($combination),
                    ],
                    [
                        $this->identicalTo('def'),
                        $this->identicalTo($importer2),
                        $this->identicalTo($exportData),
                        $this->identicalTo($combination),
                    ],
                );

        $this->invokeMethod($command, 'import', $exportData, $combination);
    }

    /**
     * Tests the executeImporter method.
     * @throws ReflectionException
     * @covers ::executeImporter
     */
    public function testExecuteImporter(): void
    {
        $name = 'abc';
        $count = 100;
        $chunkSize = 42;
        
        $exportData = $this->createMock(ExportData::class);
        $combination = $this->createMock(Combination::class);

        $importer = $this->createMock(ImporterInterface::class);
        $importer->expects($this->once())
                 ->method('count')
                 ->with($exportData)
                 ->willReturn($count);
        $importer->expects($this->once())
                 ->method('prepare')
                 ->with($this->identicalTo($combination));
        
        $process1 = $this->createMock(ImportCommandProcess::class);
        $process2 = $this->createMock(ImportCommandProcess::class);
        $process3 = $this->createMock(ImportCommandProcess::class);
        
        $processManager = $this->createMock(ProcessManagerInterface::class);
        $processManager->expects($this->exactly(3))
                       ->method('addProcess')
                       ->withConsecutive(
                           [$this->identicalTo($process1)],
                           [$this->identicalTo($process2)],
                           [$this->identicalTo($process3)],
                       );
        $processManager->expects($this->once())
                       ->method('waitForAllProcesses');
        
        $command = $this->getMockBuilder(ImportCommand::class)
                        ->onlyMethods(['createProcessManager', 'createSubProcess'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->exportDataService,
                            [],
                            $chunkSize,
                            21,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('createProcessManager')
                ->willReturn($processManager);
        $command->expects($this->exactly(3))
                ->method('createSubProcess')
                ->withConsecutive(
                    [$this->identicalTo($combination), $this->identicalTo($name), $this->identicalTo(0)],
                    [$this->identicalTo($combination), $this->identicalTo($name), $this->identicalTo(1)],
                    [$this->identicalTo($combination), $this->identicalTo($name), $this->identicalTo(2)],
                )
                ->willReturnOnConsecutiveCalls(
                    $process1,
                    $process2,
                    $process3,
                );

        $this->invokeMethod($command, 'executeImporter', $name, $importer, $exportData, $combination);
    }

    /**
     * Tests the createProcessManager method.
     * @throws ReflectionException
     * @covers ::createProcessManager
     */
    public function testCreateProcessManager(): void
    {
        $parallelProcesses = 42;
        $process = $this->createMock(ImportCommandProcess::class);

        $command = $this->getMockBuilder(ImportCommand::class)
                        ->onlyMethods(['handleProcessStart', 'handleProcessFinish'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->exportDataService,
                            [],
                            42,
                            $parallelProcesses,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('handleProcessStart')
                ->with($this->identicalTo($process));
        $command->expects($this->once())
                ->method('handleProcessFinish')
                ->with($this->identicalTo($process));

        /* @var ProcessManager $result */
        $result = $this->invokeMethod($command, 'createProcessManager');
        $this->assertSame($parallelProcesses, $this->extractProperty($result, 'numberOfParallelProcesses'));

        $startCallback = $this->extractProperty($result, 'processStartCallback');
        $this->assertIsCallable($startCallback);
        $startCallback($process);

        $finishCallback = $this->extractProperty($result, 'processFinishCallback');
        $this->assertIsCallable($finishCallback);
        $finishCallback($process);
    }

    /**
     * Tests the handleProcessStart method.
     * @throws ReflectionException
     * @covers ::handleProcessStart
     */
    public function testHandleProcessStart(): void
    {
        $process = $this->createMock(ImportCommandProcess::class);

        $this->console->expects($this->exactly(2))
                      ->method('writeAction')
                      ->withConsecutive(
                          [$this->identicalTo('Processing batch 1')],
                          [$this->identicalTo('Processing batch 2')],
                      );

        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            [],
            42,
            21,
        );

        $this->invokeMethod($command, 'handleProcessStart', $process);
        $this->invokeMethod($command, 'handleProcessStart', $process);
    }

    /**
     * Tests the handleProcessFinish method.
     * @throws ReflectionException
     * @covers ::handleProcessFinish
     */
    public function testHandleProcessFinish(): void
    {
        $output = 'abc';

        $process = $this->createMock(ImportCommandProcess::class);
        $process->expects($this->once())
                ->method('isSuccessful')
                ->willReturn(true);
        $process->expects($this->once())
                ->method('getOutput')
                ->willReturn($output);

        $this->console->expects($this->once())
                      ->method('writeData')
                      ->with($this->identicalTo($output));

        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            [],
            42,
            21,
        );

        $this->invokeMethod($command, 'handleProcessFinish', $process);
    }

    /**
     * Tests the handleProcessFinish method.
     * @throws ReflectionException
     * @covers ::handleProcessFinish
     */
    public function testHandleProcessFinishWithError(): void
    {
        $process = $this->createMock(ImportCommandProcess::class);
        $process->expects($this->once())
                ->method('isSuccessful')
                ->willReturn(false);
        $process->expects($this->once())
                ->method('getOutput')
                ->willReturn('abc');

        $this->expectException(CommandFailureException::class);

        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            [],
            42,
            21,
        );

        $this->invokeMethod($command, 'handleProcessFinish', $process);
    }

    /**
     * Tests the createSubProcess method.
     * @throws ReflectionException
     * @covers ::createSubProcess
     */
    public function testCreateSubProcess(): void
    {
        $combinationId = Uuid::fromString('557fa643-6924-438f-b328-00dd2440cb7c');
        $combination = new Combination();
        $combination->setId($combinationId);

        $part = 'abc';
        $chunk = 3;
        $chunkSize = 42;

        $expectedResult = new ImportCommandProcess(
            CommandName::IMPORT_PART,
            $combination,
            ['abc', '126', '42'],
        );

        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            [],
            $chunkSize,
            21,
        );

        $result = $this->invokeMethod($command, 'createSubProcess', $combination, $part, $chunk);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the cleanup method.
     * @throws ReflectionException
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $importer1 = $this->createMock(ImporterInterface::class);
        $importer1->expects($this->once())
                  ->method('cleanup');
        $importer2 = $this->createMock(ImporterInterface::class);
        $importer2->expects($this->once())
                  ->method('cleanup');

        $importers = [$importer1, $importer2];


        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $importers,
            42,
            21,
        );

        $this->invokeMethod($command, 'cleanup');
    }
}
