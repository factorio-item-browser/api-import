<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Handler;

use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Exception\ErrorResponseException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Mod\ModImporterInterface;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * The handler for importing a part of a mod.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ModPartHandler implements RequestHandlerInterface
{
    /**
     * The importer.
     * @var ModImporterInterface
     */
    protected $importer;

    /**
     * The repository of the mods.
     * @var ModRepository
     */
    protected $modRepository;

    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * Initializes the handler.
     * @param ModImporterInterface $importer
     * @param ModRepository $modRepository
     * @param RegistryService $registryService
     */
    public function __construct(
        ModImporterInterface $importer,
        ModRepository $modRepository,
        RegistryService $registryService
    ) {
        $this->importer = $importer;
        $this->modRepository = $modRepository;
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
        $exportMod = $this->fetchExportMod($request->getAttribute('modName'));
        $databaseMod = $this->fetchDatabaseMod($exportMod);

        $this->importer->import($exportMod, $databaseMod);

        return new EmptyResponse();
    }

    /**
     * Fetches the export mod to the specified hash.
     * @param string $modName
     * @return ExportMod
     * @throws ErrorResponseException
     */
    protected function fetchExportMod(string $modName): ExportMod
    {
        try {
            $result = $this->registryService->getMod($modName);
        } catch (UnknownHashException $e) {
            throw new ErrorResponseException($e->getMessage(), 404, $e);
        }
        return $result;
    }

    /**
     * Fetches the mod from the database.
     * @param ExportMod $exportMod
     * @return DatabaseMod
     * @throws ErrorResponseException
     */
    protected function fetchDatabaseMod(ExportMod $exportMod): DatabaseMod
    {
        $databaseMods = $this->modRepository->findByNamesWithDependencies([$exportMod->getName()]);
        $result = reset($databaseMods);

        if (!$result instanceof DatabaseMod) {
            throw new ErrorResponseException('Mod is not present in database.', 400);
        }
        return $result;
    }
}
