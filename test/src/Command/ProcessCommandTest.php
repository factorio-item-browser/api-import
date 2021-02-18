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
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Exception\CommandFailureException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Process\ImportCommandProcess;
use FactorioItemBrowser\CombinationApi\Client\ClientInterface;
use FactorioItemBrowser\CombinationApi\Client\Constant\JobStatus;
use FactorioItemBrowser\CombinationApi\Client\Constant\ListOrder;
use FactorioItemBrowser\CombinationApi\Client\Request\Job\ListRequest;
use FactorioItemBrowser\CombinationApi\Client\Request\Job\UpdateRequest;
use FactorioItemBrowser\CombinationApi\Client\Response\Job\DetailsResponse;
use FactorioItemBrowser\CombinationApi\Client\Response\Job\ListResponse;
use FactorioItemBrowser\CombinationApi\Client\Transfer\Job;
use GuzzleHttp\Promise\FulfilledPromise;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The PHPUnit test of the ProcessCommand class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Command\ProcessCommand
 */
class ProcessCommandTest extends TestCase
{
    use ReflectionTrait;

    /** @var ClientInterface&MockObject */
    private ClientInterface $combinationApiClient;
    /** @var CombinationRepository&MockObject */
    private CombinationRepository $combinationRepository;
    /** @var Console&MockObject */
    private Console $console;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->combinationApiClient = $this->createMock(ClientInterface::class);
        $this->combinationRepository = $this->createMock(CombinationRepository::class);
        $this->console = $this->createMock(Console::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return ProcessCommand&MockObject
     */
    private function createInstance(array $mockedMethods = []): ProcessCommand
    {
        return $this->getMockBuilder(ProcessCommand::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->combinationApiClient,
                        $this->combinationRepository,
                        $this->console,
                        $this->entityManager,
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
                 ->with($this->identicalTo(CommandName::PROCESS));
        $instance->expects($this->once())
                 ->method('setDescription')
                 ->with($this->isType('string'));

        $this->invokeMethod($instance, 'configure');
    }

    /**
     * @throws Exception
     */
    public function testExecute(): void
    {
        $expectedResult = 0;

        $job = $this->createMock(Job::class);
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->console->expects($this->never())
                      ->method('writeMessage');
        $this->console->expects($this->never())
                      ->method('writeException');

        $instance = $this->createInstance(['fetchNextJob', 'processJob', 'updateJobStatus']);
        $instance->expects($this->once())
                 ->method('fetchNextJob')
                 ->willReturn($job);
        $instance->expects($this->once())
                 ->method('processJob')
                 ->with($this->identicalTo($job));
        $instance->expects($this->never())
                 ->method('updateJobStatus');

        $result = $this->invokeMethod($instance, 'execute', $input, $output);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @throws Exception
     */
    public function testExecuteWithoutJob(): void
    {
        $expectedResult = 0;

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->console->expects($this->once())
                      ->method('writeMessage')
                      ->with($this->identicalTo('No job to import. Done.'));
        $this->console->expects($this->never())
                      ->method('writeException');

        $instance = $this->createInstance(['fetchNextJob', 'processJob', 'updateJobStatus']);
        $instance->expects($this->once())
                 ->method('fetchNextJob')
                 ->willReturn(null);
        $instance->expects($this->never())
                 ->method('processJob');
        $instance->expects($this->never())
                 ->method('updateJobStatus');

        $result = $this->invokeMethod($instance, 'execute', $input, $output);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @throws Exception
     */
    public function testExecuteWithImportException(): void
    {
        $expectedResult = 1;

        $job = $this->createMock(Job::class);
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->console->expects($this->never())
                      ->method('writeMessage');
        $this->console->expects($this->never())
                      ->method('writeException');

        $instance = $this->createInstance(['fetchNextJob', 'processJob', 'updateJobStatus']);
        $instance->expects($this->once())
                 ->method('fetchNextJob')
                 ->willReturn($job);
        $instance->expects($this->once())
                 ->method('processJob')
                 ->with($this->identicalTo($job))
                 ->willThrowException(new ImportException('abc'));
        $instance->expects($this->once())
                 ->method('updateJobStatus')
                 ->with($this->identicalTo($job), $this->identicalTo(JobStatus::ERROR), $this->isType('string'));

        $result = $this->invokeMethod($instance, 'execute', $input, $output);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @throws Exception
     */
    public function testExecuteWithGenericException(): void
    {
        $expectedResult = 1;

        $exception = $this->createMock(Exception::class);
        $job = $this->createMock(Job::class);
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->console->expects($this->never())
                      ->method('writeMessage');
        $this->console->expects($this->once())
                      ->method('writeException')
                      ->with($this->identicalTo($exception));

        $instance = $this->createInstance(['fetchNextJob', 'processJob', 'updateJobStatus']);
        $instance->expects($this->once())
                 ->method('fetchNextJob')
                 ->willReturn($job);
        $instance->expects($this->once())
                 ->method('processJob')
                 ->with($this->identicalTo($job))
                 ->willThrowException($exception);
        $instance->expects($this->never())
                 ->method('updateJobStatus');

        $result = $this->invokeMethod($instance, 'execute', $input, $output);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFetchNextJob(): void
    {
        $job = $this->createMock(Job::class);

        $expectedRequest = new ListRequest();
        $expectedRequest->status = JobStatus::UPLOADED;
        $expectedRequest->order = ListOrder::PRIORITY;
        $expectedRequest->limit = 1;

        $response = new ListResponse();
        $response->jobs = [$job];

        $this->combinationApiClient->expects($this->once())
                                   ->method('sendRequest')
                                   ->with($this->equalTo($expectedRequest))
                                   ->willReturn(new FulfilledPromise($response));

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'fetchNextJob');

        $this->assertSame($job, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFetchNextJobWithoutJob(): void
    {
        $expectedRequest = new ListRequest();
        $expectedRequest->status = JobStatus::UPLOADED;
        $expectedRequest->order = ListOrder::PRIORITY;
        $expectedRequest->limit = 1;

        $response = new ListResponse();

        $this->combinationApiClient->expects($this->once())
                                   ->method('sendRequest')
                                   ->with($this->equalTo($expectedRequest))
                                   ->willReturn(new FulfilledPromise($response));

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'fetchNextJob');

        $this->assertNull($result);
    }

    /**
     * @throws ReflectionException
     */
    public function testProcessJob(): void
    {
        $job1 = $this->createMock(Job::class);
        $job2 = $this->createMock(Job::class);
        $job3 = $this->createMock(Job::class);
        $combination = $this->createMock(Combination::class);

        $instance = $this->createInstance(['updateJobStatus', 'fetchCombination', 'runImportCommand']);
        $instance->expects($this->exactly(2))
                 ->method('updateJobStatus')
                 ->withConsecutive(
                     [$this->identicalTo($job1), $this->identicalTo(JobStatus::IMPORTING)],
                     [$this->identicalTo($job2), $this->identicalTo(JobStatus::DONE)]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $job2,
                     $job3
                 );
        $instance->expects($this->once())
                 ->method('fetchCombination')
                 ->with($this->identicalTo($job2))
                 ->willReturn($combination);
        $instance->expects($this->once())
                 ->method('runImportCommand')
                 ->with($this->identicalTo(CommandName::IMPORT), $this->identicalTo($combination));

        $this->invokeMethod($instance, 'processJob', $job1);
    }

    /**
     * @throws ReflectionException
     */
    public function testUpdateJobStatus(): void
    {
        $status = 'def';
        $errorMessage = 'ghi';

        $job = new Job();
        $job->id = 'abc';

        $expectedRequest = new UpdateRequest();
        $expectedRequest->id = 'abc';
        $expectedRequest->status = 'def';
        $expectedRequest->errorMessage = 'ghi';

        $response = $this->createMock(DetailsResponse::class);

        $this->combinationApiClient->expects($this->once())
                                   ->method('sendRequest')
                                   ->with($this->equalTo($expectedRequest))
                                   ->willReturn(new FulfilledPromise($response));

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'updateJobStatus', $job, $status, $errorMessage);

        $this->assertSame($response, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFetchCombinationWithExistingCombination(): void
    {
        $combinationIdString = '70acdb0f-36ca-4b30-9687-2baaade94cd3';
        $combinationId = Uuid::fromString($combinationIdString);

        $combination = $this->createMock(Combination::class);

        $job = new Job();
        $job->combinationId = $combinationIdString;

        $this->combinationRepository->expects($this->once())
                                    ->method('findById')
                                    ->with($this->equalTo($combinationId))
                                    ->willReturn($combination);

        $this->entityManager->expects($this->never())
                            ->method('persist');
        $this->entityManager->expects($this->never())
                            ->method('flush');

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'fetchCombination', $job);

        $this->assertSame($combination, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFetchCombinationWithoutExistingCombination(): void
    {
        $combinationIdString = '70acdb0f-36ca-4b30-9687-2baaade94cd3';
        $combinationId = Uuid::fromString($combinationIdString);

        $job = new Job();
        $job->combinationId = $combinationIdString;

        $this->combinationRepository->expects($this->once())
                                    ->method('findById')
                                    ->with($this->equalTo($combinationId))
                                    ->willReturn(null);

        $this->entityManager->expects($this->once())
                            ->method('persist')
                            ->with($this->isInstanceOf(Combination::class));
        $this->entityManager->expects($this->once())
                            ->method('flush');

        $instance = $this->createInstance();
        /* @var Combination $result */
        $result = $this->invokeMethod($instance, 'fetchCombination', $job);

        $this->assertEquals($combinationId, $result->getId());
        $this->assertNotNull($result->getImportTime());
        $this->assertNotNull($result->getLastUsageTime());
    }

    /**
     * @throws ReflectionException
     */
    public function testRunImportCommand(): void
    {
        $instanceName = 'abc';
        $outputData = 'def';

        $combination = $this->createMock(Combination::class);

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

        $instance = $this->createInstance(['createImportCommandProcess']);
        $instance->expects($this->once())
                 ->method('createImportCommandProcess')
                 ->with($this->identicalTo($instanceName), $this->identicalTo($combination))
                 ->willReturn($process);

        $this->invokeMethod($instance, 'runImportCommand', $instanceName, $combination);
    }

    /**
     * @throws ReflectionException
     */
    public function testRunImportCommandWithFailure(): void
    {
        $instanceName = 'abc';
        $outputData = 'def';

        $combination = $this->createMock(Combination::class);

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

        $instance = $this->createInstance(['createImportCommandProcess']);
        $instance->expects($this->once())
                 ->method('createImportCommandProcess')
                 ->with($this->identicalTo($instanceName), $this->identicalTo($combination))
                 ->willReturn($process);

        $this->invokeMethod($instance, 'runImportCommand', $instanceName, $combination);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateImportCommandProcess(): void
    {
        $instanceName = 'abc';

        $combination = $this->createMock(Combination::class);

        $expectedResult = new ImportCommandProcess($instanceName, $combination);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'createImportCommandProcess', $instanceName, $combination);

        $this->assertEquals($expectedResult, $result);
    }
}
