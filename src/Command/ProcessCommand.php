<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Exception\CommandFailureException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Process\ImportCommandProcess;
use FactorioItemBrowser\ExportQueue\Client\Client\Facade;
use FactorioItemBrowser\ExportQueue\Client\Constant\JobStatus;
use FactorioItemBrowser\ExportQueue\Client\Entity\Job;
use FactorioItemBrowser\ExportQueue\Client\Exception\ClientException;
use FactorioItemBrowser\ExportQueue\Client\Request\Job\ListRequest;
use FactorioItemBrowser\ExportQueue\Client\Request\Job\UpdateRequest;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * The command for processing the next job in the import queue.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ProcessCommand implements CommandInterface
{
    /**
     * The console.
     * @var Console
     */
    protected $console;

    /**
     * The combination repository.
     * @var CombinationRepository
     */
    protected $combinationRepository;

    /**
     * The entity manager.
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * The export queue facade.
     * @var Facade
     */
    protected $exportQueueFacade;

    /**
     * Initializes the command.
     * @param CombinationRepository $combinationRepository
     * @param Console $console
     * @param EntityManagerInterface $entityManager
     * @param Facade $exportQueueFacade
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        Console $console,
        EntityManagerInterface $entityManager,
        Facade $exportQueueFacade
    ) {
        $this->combinationRepository = $combinationRepository;
        $this->console = $console;
        $this->entityManager = $entityManager;
        $this->exportQueueFacade = $exportQueueFacade;
    }

    /**
     * Invokes the command.
     * @param Route $route
     * @param AdapterInterface $consoleAdapter
     * @return int
     * @throws Exception
     */
    public function __invoke(Route $route, AdapterInterface $consoleAdapter): int
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
        $request->setStatus(JobStatus::UPLOADED)
                ->setLimit(1);

        $response = $this->exportQueueFacade->getJobList($request);
        return $response->getJobs()[0] ?? null;
    }

    /**
     * Processes the job.
     * @param Job $job
     * @throws ImportException
     * @throws Exception
     */
    protected function processJob(Job $job): void
    {
        $this->console->writeHeadline(sprintf('Importing combination %s', $job->getCombinationId()));

        $job = $this->updateJobStatus($job, JobStatus::IMPORTING);
        $combination = $this->fetchCombination($job);

        $this->runImportCommand('import', $combination);
        $this->runImportCommand('import-images', $combination);
        $this->runImportCommand('import-translations', $combination);

        $this->updateJobStatus($job, JobStatus::DONE);
        $this->console->writeStep('Done.');
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
        $request->setJobId($job->getId())
                ->setStatus($status)
                ->setErrorMessage($errorMessage);

        return $this->exportQueueFacade->updateJob($request);
    }

    /**
     * Fetches the combination or creates a new one if it does not yet exist.
     * @param Job $job
     * @return Combination
     * @throws Exception
     */
    protected function fetchCombination(Job $job): Combination
    {
        $combinationId = Uuid::fromString($job->getCombinationId());
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
        $process->run(function ($type, $data) {
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
     * @return Process
     */
    protected function createImportCommandProcess(string $commandName, Combination $combination): Process
    {
        return new ImportCommandProcess($commandName, $combination);
    }
}
