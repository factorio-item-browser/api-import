<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\SymfonyProcessManager\ProcessManager;
use BluePsyduck\SymfonyProcessManager\ProcessManagerInterface;
use BluePsyduck\TestHelper\ReflectionTrait;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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
 * @covers \FactorioItemBrowser\Api\Import\Command\ImportCommand
 */
class ImportCommandTest extends TestCase
{
    use ReflectionTrait;

    /** @var CombinationRepository&MockObject */
    private CombinationRepository $combinationRepository;
    /** @var Console&MockObject */
    private Console $console;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var ExportDataService&MockObject */
    private ExportDataService $exportDataService;
    /** @var array<string, ImporterInterface> */
    private array $importers = [];
    private int $chunkSize = 42;
    private int $numberOfParallelProcesses = 21;

    protected function setUp(): void
    {
        $this->combinationRepository = $this->createMock(CombinationRepository::class);
        $this->console = $this->createMock(Console::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->exportDataService = $this->createMock(ExportDataService::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return ImportCommand&MockObject
     */
    private function createInstance(array $mockedMethods = []): ImportCommand
    {
        return $this->getMockBuilder(ImportCommand::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->combinationRepository,
                        $this->console,
                        $this->entityManager,
                        $this->exportDataService,
                        $this->importers,
                        $this->chunkSize,
                        $this->numberOfParallelProcesses,
                    ])
                    ->getMock();
    }

    /**
     * @throws ReflectionException
     */
    public function testConstruct(): void
    {
        $this->importers = [
            'abc' => $this->createMock(ImporterInterface::class),
            'def' => $this->createMock(ImporterInterface::class),
        ];

        $instance = $this->createInstance();

        $this->assertSame($this->combinationRepository, $this->extractProperty($instance, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($instance, 'console'));
        $this->assertSame($this->entityManager, $this->extractProperty($instance, 'entityManager'));
        $this->assertSame($this->exportDataService, $this->extractProperty($instance, 'exportDataService'));
        $this->assertSame($this->importers, $this->extractProperty($instance, 'importers'));
        $this->assertSame($this->chunkSize, $this->extractProperty($instance, 'chunkSize'));
        $this->assertSame(
            $this->numberOfParallelProcesses,
            $this->extractProperty($instance, 'numberOfParallelProcesses'),
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testConfigure(): void
    {
        $instance = $this->createInstance(['setName', 'setDescription', 'addArgument']);
        $instance->expects($this->once())
                 ->method('setName')
                 ->with($this->identicalTo(CommandName::IMPORT));
        $instance->expects($this->once())
                 ->method('setDescription')
                 ->with($this->isType('string'));
        $instance->expects($this->once())
                 ->method('addArgument')
                 ->with(
                     $this->identicalTo('combination'),
                     $this->identicalTo(InputArgument::REQUIRED),
                     $this->isType('string')
                 );

        $this->invokeMethod($instance, 'configure');
    }

    /**
     * @throws ReflectionException
     */
    public function testImport(): void
    {
        $exportData = $this->createMock(ExportData::class);
        $combination = $this->createMock(Combination::class);

        $importer1 = $this->createMock(ImporterInterface::class);
        $importer2 = $this->createMock(ImporterInterface::class);

        $this->importers = [
            'abc' => $importer1,
            'def' => $importer2,
        ];

        $instance = $this->createInstance(['executeImporter', 'cleanup', 'updateImportTime']);
        $instance->expects($this->exactly(2))
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
        $instance->expects($this->once())
                 ->method('cleanup');
        $instance->expects($this->once())
                 ->method('updateImportTime')
                 ->with($this->identicalTo($combination));

        $this->invokeMethod($instance, 'import', $exportData, $combination);
    }

    /**
     * @throws ReflectionException
     */
    public function testExecuteImporter(): void
    {
        $name = 'abc';
        $count = 100;
        $this->chunkSize = 42;

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

        $instance = $this->createInstance(['createProcessManager', 'createSubProcess']);
        $instance->expects($this->once())
                 ->method('createProcessManager')
                 ->willReturn($processManager);
        $instance->expects($this->exactly(3))
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

        $this->invokeMethod($instance, 'executeImporter', $name, $importer, $exportData, $combination);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateProcessManager(): void
    {
        $process = $this->createMock(ImportCommandProcess::class);

        $this->console->expects($this->exactly(2))
                      ->method('writeAction')
                      ->withConsecutive(
                          [$this->identicalTo('Processing chunk 1')],
                          [$this->identicalTo('Processing chunk 2')],
                      );

        $instance = $this->createInstance(['handleProcessFinish']);
        $instance->expects($this->once())
                ->method('handleProcessFinish')
                ->with($this->identicalTo($process));

        /* @var ProcessManager $result */
        $result = $this->invokeMethod($instance, 'createProcessManager');
        $this->assertSame(
            $this->numberOfParallelProcesses,
            $this->extractProperty($result, 'numberOfParallelProcesses'),
        );

        $startCallback = $this->extractProperty($result, 'processStartCallback');
        $this->assertIsCallable($startCallback);
        $startCallback($process);
        $startCallback($process);

        $finishCallback = $this->extractProperty($result, 'processFinishCallback');
        $this->assertIsCallable($finishCallback);
        $finishCallback($process);
    }

    /**
     * @throws ReflectionException
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

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'handleProcessFinish', $process);
    }

    /**
     * @throws ReflectionException
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

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'handleProcessFinish', $process);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateSubProcess(): void
    {
        $combinationId = Uuid::fromString('557fa643-6924-438f-b328-00dd2440cb7c');
        $combination = new Combination();
        $combination->setId($combinationId);

        $part = 'abc';
        $chunk = 3;

        $expectedResult = new ImportCommandProcess(
            CommandName::IMPORT_PART,
            $combination,
            ['abc', '126', '42'],
        );

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'createSubProcess', $combination, $part, $chunk);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testCleanup(): void
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

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'cleanup');
    }

    /**
     * @throws ReflectionException
     */
    public function testUpdateImportTime(): void
    {
        $combination = $this->createMock(Combination::class);
        $combination->expects($this->once())
                    ->method('setImportTime')
                    ->with($this->isInstanceOf(DateTime::class));

        $this->entityManager->expects($this->once())
                            ->method('persist')
                            ->with($this->identicalTo($combination));
        $this->entityManager->expects($this->once())
                            ->method('flush');

        $instance = $this->createInstance();

        $this->invokeMethod($instance, 'updateImportTime', $combination);
    }
}
