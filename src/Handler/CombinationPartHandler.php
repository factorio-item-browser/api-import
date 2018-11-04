<?php

namespace FactorioItemBrowser\Api\Import\Handler;

use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\ModCombinationRepository;
use FactorioItemBrowser\Api\Import\Exception\ErrorResponseException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * The handler for importing a part of a combination.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CombinationPartHandler implements RequestHandlerInterface
{
    /**
     * The importer.
     * @var ImporterInterface
     */
    protected $importer;

    /**
     * The repository of the mod combinations.
     * @var ModCombinationRepository
     */
    protected $modCombinationRepository;

    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * ItemHandler constructor.
     * @param ImporterInterface $importer
     * @param ModCombinationRepository $modCombinationRepository
     * @param RegistryService $registryService
     */
    public function __construct(
        ImporterInterface $importer,
        ModCombinationRepository $modCombinationRepository,
        RegistryService $registryService
    ) {
        $this->importer = $importer;
        $this->modCombinationRepository = $modCombinationRepository;
        $this->registryService = $registryService;
    }

    /**
     * Handle the request and return a response.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ImportException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $exportCombination = $this->fetchExportCombination($request->getAttribute('combinationHash'));
        $databaseCombination = $this->fetchDatabaseCombination($exportCombination);

        $this->importer->import($exportCombination, $databaseCombination);

        return new EmptyResponse();
    }

    /**
     * Fetches the export combination to the specified hash.
     * @param string $combinationHash
     * @return ExportCombination
     * @throws ErrorResponseException
     */
    protected function fetchExportCombination(string $combinationHash): ExportCombination
    {
        try {
            $result = $this->registryService->getCombination($combinationHash);
        } catch (UnknownHashException $e) {
            throw new ErrorResponseException($e->getMessage(), 404, $e);
        }
        return $result;
    }

    /**
     * Fetches the combination from the database.
     * @param ExportCombination $exportCombination
     * @return DatabaseCombination
     * @throws ErrorResponseException
     */
    protected function fetchDatabaseCombination(ExportCombination $exportCombination): DatabaseCombination
    {
        $databaseCombinations = $this->modCombinationRepository->findByNames([$exportCombination->getName()]);
        $result = reset($databaseCombinations);

        if (!$result instanceof DatabaseCombination) {
            throw new ErrorResponseException('Combination is not present in database.', 400);
        }
        return $result;
    }
}
