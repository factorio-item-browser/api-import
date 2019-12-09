<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Command\ProcessCommand;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Exception\CommandFailureException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Process\ImportCommandProcess;
use FactorioItemBrowser\ExportQueue\Client\Client\Facade;
use FactorioItemBrowser\ExportQueue\Client\Constant\JobStatus;
use FactorioItemBrowser\ExportQueue\Client\Entity\Job;
use FactorioItemBrowser\ExportQueue\Client\Request\Job\ListRequest;
use FactorioItemBrowser\ExportQueue\Client\Request\Job\UpdateRequest;
use FactorioItemBrowser\ExportQueue\Client\Response\Job\DetailsResponse;
use FactorioItemBrowser\ExportQueue\Client\Response\Job\ListResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * The PHPUnit test of the ProcessCommand class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Command\ProcessCommand
 */
class ProcessCommandTest extends TestCase
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
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

    /**
     * The mocked export queue facade.
     * @var Facade&MockObject
     */
    protected $exportQueueFacade;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->combinationRepository = $this->createMock(CombinationRepository::class);
        $this->console = $this->createMock(Console::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->exportQueueFacade = $this->createMock(Facade::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $command = new ProcessCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportQueueFacade
        );

        $this->assertSame($this->combinationRepository, $this->extractProperty($command, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($command, 'console'));
        $this->assertSame($this->entityManager, $this->extractProperty($command, 'entityManager'));
        $this->assertSame($this->exportQueueFacade, $this->extractProperty($command, 'exportQueueFacade'));
    }

    /**
     * Tests the invoking.
     * @throws Exception
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        $expectedResult = 0;

        /* @var Job&MockObject $job */
        $job = $this->createMock(Job::class);
        /* @var Route&MockObject $route */
        $route = $this->createMock(Route::class);
        /* @var AdapterInterface&MockObject $consoleAdapter */
        $consoleAdapter = $this->createMock(AdapterInterface::class);

        $this->console->expects($this->never())
                      ->method('writeMessage');
        $this->console->expects($this->never())
                      ->method('writeException');

        /* @var ProcessCommand&MockObject $command */
        $command = $this->getMockBuilder(ProcessCommand::class)
                        ->onlyMethods(['fetchNextJob', 'processJob', 'updateJobStatus'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportQueueFacade,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('fetchNextJob')
                ->willReturn($job);
        $command->expects($this->once())
                ->method('processJob')
                ->with($this->identicalTo($job));
        $command->expects($this->never())
                ->method('updateJobStatus');

        $result = $command($route, $consoleAdapter);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the invoking.
     * @throws Exception
     * @covers ::__invoke
     */
    public function testInvokeWithoutJob(): void
    {
        $expectedResult = 0;

        /* @var Route&MockObject $route */
        $route = $this->createMock(Route::class);
        /* @var AdapterInterface&MockObject $consoleAdapter */
        $consoleAdapter = $this->createMock(AdapterInterface::class);

        $this->console->expects($this->once())
                      ->method('writeMessage')
                      ->with($this->identicalTo('No job to import. Done.'));
        $this->console->expects($this->never())
                      ->method('writeException');

        /* @var ProcessCommand&MockObject $command */
        $command = $this->getMockBuilder(ProcessCommand::class)
                        ->onlyMethods(['fetchNextJob', 'processJob', 'updateJobStatus'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportQueueFacade,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('fetchNextJob')
                ->willReturn(null);
        $command->expects($this->never())
                ->method('processJob');
        $command->expects($this->never())
                ->method('updateJobStatus');

        $result = $command($route, $consoleAdapter);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the invoking.
     * @throws Exception
     * @covers ::__invoke
     */
    public function testInvokeWithImportException(): void
    {
        $expectedResult = 1;

        /* @var Job&MockObject $job */
        $job = $this->createMock(Job::class);
        /* @var Route&MockObject $route */
        $route = $this->createMock(Route::class);
        /* @var AdapterInterface&MockObject $consoleAdapter */
        $consoleAdapter = $this->createMock(AdapterInterface::class);

        $this->console->expects($this->never())
                      ->method('writeMessage');
        $this->console->expects($this->never())
                      ->method('writeException');

        /* @var ProcessCommand&MockObject $command */
        $command = $this->getMockBuilder(ProcessCommand::class)
                        ->onlyMethods(['fetchNextJob', 'processJob', 'updateJobStatus'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportQueueFacade,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('fetchNextJob')
                ->willReturn($job);
        $command->expects($this->once())
                ->method('processJob')
                ->with($this->identicalTo($job))
                ->willThrowException(new ImportException('abc'));
        $command->expects($this->once())
                ->method('updateJobStatus')
                ->with($this->identicalTo($job), $this->identicalTo(JobStatus::ERROR), $this->isType('string'));

        $result = $command($route, $consoleAdapter);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the invoking.
     * @throws Exception
     * @covers ::__invoke
     */
    public function testInvokeWithGenericException(): void
    {
        $expectedResult = 1;

        /* @var Exception&MockObject $exception */
        $exception = $this->createMock(Exception::class);
        /* @var Job&MockObject $job */
        $job = $this->createMock(Job::class);
        /* @var Route&MockObject $route */
        $route = $this->createMock(Route::class);
        /* @var AdapterInterface&MockObject $consoleAdapter */
        $consoleAdapter = $this->createMock(AdapterInterface::class);

        $this->console->expects($this->never())
                      ->method('writeMessage');
        $this->console->expects($this->once())
                      ->method('writeException')
                      ->with($this->identicalTo($exception));

        /* @var ProcessCommand&MockObject $command */
        $command = $this->getMockBuilder(ProcessCommand::class)
                        ->onlyMethods(['fetchNextJob', 'processJob', 'updateJobStatus'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportQueueFacade,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('fetchNextJob')
                ->willReturn($job);
        $command->expects($this->once())
                ->method('processJob')
                ->with($this->identicalTo($job))
                ->willThrowException($exception);
        $command->expects($this->never())
                ->method('updateJobStatus');

        $result = $command($route, $consoleAdapter);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the fetchNextJob method.
     * @throws ReflectionException
     * @covers ::fetchNextJob
     */
    public function testFetchNextJob(): void
    {
        /* @var Job&MockObject $job */
        $job = $this->createMock(Job::class);

        $expectedRequest = new ListRequest();
        $expectedRequest->setStatus(JobStatus::UPLOADED)
                        ->setLimit(1);

        $response = new ListResponse();
        $response->setJobs([$job]);

        $this->exportQueueFacade->expects($this->once())
                                ->method('getJobList')
                                ->with($this->equalTo($expectedRequest))
                                ->willReturn($response);

        $command = new ProcessCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportQueueFacade
        );
        $result = $this->invokeMethod($command, 'fetchNextJob');

        $this->assertSame($job, $result);
    }

    /**
     * Tests the fetchNextJob method.
     * @throws ReflectionException
     * @covers ::fetchNextJob
     */
    public function testFetchNextJobWithoutJob(): void
    {
        $expectedRequest = new ListRequest();
        $expectedRequest->setStatus(JobStatus::UPLOADED)
                        ->setLimit(1);

        $response = new ListResponse();

        $this->exportQueueFacade->expects($this->once())
                                ->method('getJobList')
                                ->with($this->equalTo($expectedRequest))
                                ->willReturn($response);

        $command = new ProcessCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportQueueFacade
        );
        $result = $this->invokeMethod($command, 'fetchNextJob');

        $this->assertNull($result);
    }

    /**
     * Tests the processJob method.
     * @throws ReflectionException
     * @covers ::processJob
     */
    public function testProcessJob(): void
    {
        $combinationId = 'abc';

        /* @var Job&MockObject $job1 */
        $job1 = $this->createMock(Job::class);
        $job1->expects($this->once())
             ->method('getCombinationId')
             ->willReturn($combinationId);

        /* @var Job&MockObject $job2 */
        $job2 = $this->createMock(Job::class);
        /* @var Job&MockObject $job3 */
        $job3 = $this->createMock(Job::class);
        /* @var Combination&MockObject $combination */
        $combination = $this->createMock(Combination::class);

        $this->console->expects($this->once())
                      ->method('writeHeadline')
                      ->with($this->identicalTo('Importing combination abc'));
        $this->console->expects($this->once())
                      ->method('writeStep')
                      ->with($this->identicalTo('Done.'));

        /* @var ProcessCommand&MockObject $command */
        $command = $this->getMockBuilder(ProcessCommand::class)
                        ->onlyMethods(['updateJobStatus', 'fetchCombination', 'runImportCommand'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportQueueFacade,
                        ])
                        ->getMock();
        $command->expects($this->exactly(2))
                ->method('updateJobStatus')
                ->withConsecutive(
                    [$this->identicalTo($job1), $this->identicalTo(JobStatus::IMPORTING)],
                    [$this->identicalTo($job2), $this->identicalTo(JobStatus::DONE)]
                )
                ->willReturnOnConsecutiveCalls(
                    $job2,
                    $job3
                );
        $command->expects($this->once())
                ->method('fetchCombination')
                ->with($this->identicalTo($job2))
                ->willReturn($combination);
        $command->expects($this->exactly(3))
                ->method('runImportCommand')
                ->withConsecutive(
                    [$this->identicalTo('import'), $this->identicalTo($combination)],
                    [$this->identicalTo('import-images'), $this->identicalTo($combination)],
                    [$this->identicalTo('import-translations'), $this->identicalTo($combination)]
                );

        $this->invokeMethod($command, 'processJob', $job1);
    }

    /**
     * Tests the updateJobStatus method.
     * @throws ReflectionException
     * @covers ::updateJobStatus
     */
    public function testUpdateJobStatus(): void
    {
        $status = 'def';
        $errorMessage = 'ghi';

        $job = new Job();
        $job->setId('abc');

        $expectedRequest = new UpdateRequest();
        $expectedRequest->setJobId('abc')
                        ->setStatus('def')
                        ->setErrorMessage('ghi');

        /* @var DetailsResponse&MockObject $response */
        $response = $this->createMock(DetailsResponse::class);

        $this->exportQueueFacade->expects($this->once())
                                ->method('updateJob')
                                ->with($this->equalTo($expectedRequest))
                                ->willReturn($response);

        $command = new ProcessCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportQueueFacade
        );
        $result = $this->invokeMethod($command, 'updateJobStatus', $job, $status, $errorMessage);

        $this->assertSame($response, $result);
    }

    /**
     * Tests the fetchCombination method.
     * @throws ReflectionException
     * @covers ::fetchCombination
     */
    public function testFetchCombinationWithExistingCombination(): void
    {
        $combinationIdString = '70acdb0f-36ca-4b30-9687-2baaade94cd3';
        $combinationId = Uuid::fromString($combinationIdString);

        /* @var Combination&MockObject $combination */
        $combination = $this->createMock(Combination::class);

        $job = new Job();
        $job->setCombinationId($combinationIdString);

        $this->combinationRepository->expects($this->once())
                                    ->method('findById')
                                    ->with($this->equalTo($combinationId))
                                    ->willReturn($combination);

        $this->entityManager->expects($this->never())
                            ->method('persist');
        $this->entityManager->expects($this->never())
                            ->method('flush');

        $command = new ProcessCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportQueueFacade
        );
        $result = $this->invokeMethod($command, 'fetchCombination', $job);

        $this->assertSame($combination, $result);
    }

    /**
     * Tests the fetchCombination method.
     * @throws ReflectionException
     * @covers ::fetchCombination
     */
    public function testFetchCombinationWithoutExistingCombination(): void
    {
        $combinationIdString = '70acdb0f-36ca-4b30-9687-2baaade94cd3';
        $combinationId = Uuid::fromString($combinationIdString);

        $job = new Job();
        $job->setCombinationId($combinationIdString);

        $this->combinationRepository->expects($this->once())
                                    ->method('findById')
                                    ->with($this->equalTo($combinationId))
                                    ->willReturn(null);

        $this->entityManager->expects($this->once())
                            ->method('persist')
                            ->with($this->isInstanceOf(Combination::class));
        $this->entityManager->expects($this->once())
                            ->method('flush');

        $command = new ProcessCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportQueueFacade
        );
        /* @var Combination $result */
        $result = $this->invokeMethod($command, 'fetchCombination', $job);

        $this->assertEquals($combinationId, $result->getId());
        $this->assertNotNull($result->getImportTime());
        $this->assertNotNull($result->getLastUsageTime());
    }

    /**
     * Tests the runImportCommand method.
     * @throws ReflectionException
     * @covers ::runImportCommand
     */
    public function testRunImportCommand(): void
    {
        $commandName = 'abc';
        $outputData = 'def';

        /* @var Combination&MockObject $combination */
        $combination = $this->createMock(Combination::class);

        /* @var ImportCommandProcess&MockObject $process */
        $process = $this->createMock(ImportCommandProcess::class);
        $process->expects($this->once())
                ->method('run')
                ->with($this->callback(function ($callback) use ($outputData): bool {
                    $this->assertIsCallable($callback);
                    $callback('foo', $outputData);
                    return true;
                }));
        $process->expects($this->once())
                ->method('isSuccessful')
                ->willReturn(true);

        $this->console->expects($this->once())
                      ->method('writeData')
                      ->with($this->identicalTo($outputData));

        /* @var ProcessCommand&MockObject $command */
        $command = $this->getMockBuilder(ProcessCommand::class)
                        ->onlyMethods(['createImportCommandProcess'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportQueueFacade,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('createImportCommandProcess')
                ->with($this->identicalTo($commandName), $this->identicalTo($combination))
                ->willReturn($process);

        $this->invokeMethod($command, 'runImportCommand', $commandName, $combination);
    }

    /**
     * Tests the runImportCommand method.
     * @throws ReflectionException
     * @covers ::runImportCommand
     */
    public function testRunImportCommandWithFailure(): void
    {
        $commandName = 'abc';
        $outputData = 'def';

        /* @var Combination&MockObject $combination */
        $combination = $this->createMock(Combination::class);

        /* @var ImportCommandProcess&MockObject $process */
        $process = $this->createMock(ImportCommandProcess::class);
        $process->expects($this->once())
                ->method('run')
                ->with($this->callback(function ($callback) use ($outputData): bool {
                    $this->assertIsCallable($callback);
                    $callback('foo', $outputData);
                    return true;
                }));
        $process->expects($this->once())
                ->method('isSuccessful')
                ->willReturn(false);
        $process->expects($this->once())
                ->method('getOutput')
                ->willReturn($outputData);

        $this->console->expects($this->once())
                      ->method('writeData')
                      ->with($this->identicalTo($outputData));

        $this->expectException(CommandFailureException::class);

        /* @var ProcessCommand&MockObject $command */
        $command = $this->getMockBuilder(ProcessCommand::class)
                        ->onlyMethods(['createImportCommandProcess'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportQueueFacade,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('createImportCommandProcess')
                ->with($this->identicalTo($commandName), $this->identicalTo($combination))
                ->willReturn($process);

        $this->invokeMethod($command, 'runImportCommand', $commandName, $combination);
    }

    /**
     * Tests the createImportCommandProcess method.
     * @throws ReflectionException
     * @covers ::createImportCommandProcess
     */
    public function testCreateImportCommandProcess(): void
    {
        $commandName = 'abc';

        /* @var Combination&MockObject $combination */
        $combination = $this->createMock(Combination::class);

        $expectedResult = new ImportCommandProcess($commandName, $combination);

        $command = new ProcessCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportQueueFacade
        );
        $result = $this->invokeMethod($command, 'createImportCommandProcess', $commandName, $combination);

        $this->assertEquals($expectedResult, $result);
    }
}
