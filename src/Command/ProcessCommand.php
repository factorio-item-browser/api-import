<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
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
     * The export queue facade.
     * @var Facade
     */
    protected $exportQueueFacade;

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
     * Initializes the command.
     * @param Facade $exportQueueFacade
     * @param CombinationRepository $combinationRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        Facade $exportQueueFacade,
        CombinationRepository $combinationRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->exportQueueFacade = $exportQueueFacade;
        $this->combinationRepository = $combinationRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Invokes the command.
     * @param Route $route
     * @param AdapterInterface $consoleAdapter
     * @return int
     * @throws ClientException
     */
    public function __invoke(Route $route, AdapterInterface $consoleAdapter): int
    {
        $exportJob = $this->fetchNextJob();
        if ($exportJob === null) {
            return 0;
        }

        $exportJob = $this->updateJobStatus($exportJob, JobStatus::IMPORTING);
        $combination = $this->fetchCombination($exportJob);

        $this->runImportCommand('import', $combination);
        $this->runImportCommand('import-images', $combination);
        $this->runImportCommand('import-translations', $combination);

        $this->updateJobStatus($exportJob, JobStatus::DONE);
        return 0;
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
     */
    protected function fetchCombination(Job $job): Combination
    {
        $combinationId = Uuid::fromString($job->getCombinationId());
        $combination = $this->combinationRepository->findById($combinationId);
        if ($combination !== null) {
            return $combination;
        }

        $combination = new Combination();
        $combination->setId($combinationId);

        $this->entityManager->persist($combination);
        $this->entityManager->flush();

        return $combination;
    }

    /**
     * Runs an import command on the combination.
     * @param string $commandName
     * @param Combination $combination
     */
    protected function runImportCommand(string $commandName, Combination $combination): void
    {
        $process = new Process([
            'php',
            $_SERVER['SCRIPT_FILENAME'],
            $commandName,
            $combination->getId()->toString(),
        ], null, ['SUBCMD' => 1]);
        $process->setTimeout(0);

        $process->run();
        echo $process->getOutput();
        if (!$process->isSuccessful()) {
            echo $process->getErrorOutput();
            die;
        }
    }
}
