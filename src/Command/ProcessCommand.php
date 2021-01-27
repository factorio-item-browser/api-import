<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Exception\CommandFailureException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Process\ImportCommandProcess;
use FactorioItemBrowser\CombinationApi\Client\ClientInterface;
use FactorioItemBrowser\CombinationApi\Client\Constant\JobStatus;
use FactorioItemBrowser\CombinationApi\Client\Constant\ListOrder;
use FactorioItemBrowser\CombinationApi\Client\Exception\ClientException;
use FactorioItemBrowser\CombinationApi\Client\Request\Job\ListRequest;
use FactorioItemBrowser\CombinationApi\Client\Request\Job\UpdateRequest;
use FactorioItemBrowser\CombinationApi\Client\Response\Job\ListResponse;
use FactorioItemBrowser\CombinationApi\Client\Transfer\Job;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * The command for processing the next job in the import queue.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ProcessCommand extends Command
{
    protected ClientInterface $combinationApiClient;
    protected CombinationRepository $combinationRepository;
    protected Console $console;
    protected EntityManagerInterface $entityManager;

    public function __construct(
        ClientInterface $combinationApiClient,
        CombinationRepository $combinationRepository,
        Console $console,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();

        $this->combinationApiClient = $combinationApiClient;
        $this->combinationRepository = $combinationRepository;
        $this->console = $console;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName(CommandName::PROCESS);
        $this->setDescription('Processes an export waiting in the export queue to be processed by the importer.');
    }

    /**
     * Executes the command.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $job = null;
        try {
            $job = $this->fetchNextJob();
            if ($job === null) {
                $this->console->writeMessage('No job to import. Done.');
                return 0;
            }

            $this->processJob($job);
            return 0;
        } catch (ImportException $e) {
            if ($job instanceof Job) {
                $this->updateJobStatus($job, JobStatus::ERROR, $e->getMessage());
            }
            return 1;
        } catch (Exception $e) {
            $this->console->writeException($e);
            return 1;
        }
    }

    /**
     * Fetches the next job to process.
     * @return Job|null
     * @throws ClientException
     */
    protected function fetchNextJob(): ?Job
    {
        $request = new ListRequest();
        $request->status = JobStatus::UPLOADED;
        $request->order = ListOrder::PRIORITY;
        $request->limit = 1;

        /** @var ListResponse $response */
        $response = $this->combinationApiClient->sendRequest($request)->wait();
        return $response->jobs[0] ?? null;
    }

    /**
     * Processes the job.
     * @param Job $job
     * @throws ImportException
     * @throws Exception
     */
    protected function processJob(Job $job): void
    {
        $job = $this->updateJobStatus($job, JobStatus::IMPORTING);
        $combination = $this->fetchCombination($job);

        $this->runImportCommand(CommandName::IMPORT, $combination);

        $this->updateJobStatus($job, JobStatus::DONE);
    }

    /**
     * Updates the status of the job.
     * @param Job $job
     * @param string $status
     * @param string $errorMessage
     * @return Job
     * @throws ClientException
     */
    protected function updateJobStatus(Job $job, string $status, string $errorMessage = ''): Job
    {
        $request = new UpdateRequest();
        $request->id = $job->id;
        $request->status = $status;
        $request->errorMessage = $errorMessage;

        return $this->combinationApiClient->sendRequest($request)->wait();
    }

    /**
     * Fetches the combination or creates a new one if it does not yet exist.
     * @param Job $job
     * @return Combination
     * @throws Exception
     */
    protected function fetchCombination(Job $job): Combination
    {
        $combinationId = Uuid::fromString($job->combinationId);
        $combination = $this->combinationRepository->findById($combinationId);
        if ($combination !== null) {
            return $combination;
        }

        $combination = new Combination();
        $combination->setId($combinationId)
                    ->setImportTime(new DateTime())
                    ->setLastUsageTime(new DateTime());

        $this->entityManager->persist($combination);
        $this->entityManager->flush();

        return $combination;
    }

    /**
     * Runs an import command on the combination.
     * @param string $commandName
     * @param Combination $combination
     * @throws ImportException
     */
    protected function runImportCommand(string $commandName, Combination $combination): void
    {
        $process = $this->createImportCommandProcess($commandName, $combination);
        $process->run(function ($type, $data): void {
            $this->console->writeData($data);
        });

        if (!$process->isSuccessful()) {
            throw new CommandFailureException($process->getOutput());
        }
    }

    /**
     * Creates a process to run an import command.
     * @param string $commandName
     * @param Combination $combination
     * @return Process<string>
     */
    protected function createImportCommandProcess(string $commandName, Combination $combination): Process
    {
        return new ImportCommandProcess($commandName, $combination);
    }
}
