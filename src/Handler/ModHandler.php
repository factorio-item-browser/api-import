<?php

namespace FactorioItemBrowser\Api\Import\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Exception\ErrorResponseException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * The handler importing the basic mod.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ModHandler implements RequestHandlerInterface
{
    /**
     * The entity manager.
     * @var EntityManager
     */
    protected $entityManager;

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
     * @param EntityManager $entityManager
     * @param ModRepository $modRepository
     * @param RegistryService $registryService
     */
    public function __construct(
        EntityManager $entityManager,
        ModRepository $modRepository,
        RegistryService $registryService
    ) {
        $this->entityManager = $entityManager;
        $this->modRepository = $modRepository;
        $this->registryService = $registryService;
    }

    /**
     * Handles a request and produces a response.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ImportException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $modName = $request->getAttribute('modName');
        $exportMod = $this->fetchExportMod($modName);
        $databaseMod = $this->fetchDatabaseMod($exportMod);
        $this->mapMetaData($exportMod, $databaseMod);
        $this->flushEntities();

        return new EmptyResponse();
    }

    /**
     * Fetches the export mod with the specified name.
     * @param string $modName
     * @return ExportMod
     * @throws ErrorResponseException
     */
    protected function fetchExportMod(string $modName): ExportMod
    {
        try {
            $result = $this->registryService->getMod($modName);
        } catch (UnknownHashException $e) {
            throw new ErrorResponseException('Mod with name ' . $modName . ' not known.', 404, $e);
        }
        return $result;
    }

    /**
     * Fetches the database mod, or creates it if not yet present.
     * @param ExportMod $exportMod
     * @return DatabaseMod
     * @throws ImportException
     */
    protected function fetchDatabaseMod(ExportMod $exportMod): DatabaseMod
    {
        $databaseMods = $this->modRepository->findByNamesWithDependencies([$exportMod->getName()]);
        $result = reset($databaseMods);

        if (!$result instanceof DatabaseMod) {
            $result = $this->createDatabaseMod($exportMod);
        }
        return $result;
    }

    /**
     * Creates a new database entity for the specified mod.
     * @param ExportMod $exportMod
     * @return DatabaseMod
     * @throws ImportException
     */
    protected function createDatabaseMod(ExportMod $exportMod): DatabaseMod
    {
        $result = new DatabaseMod($exportMod->getName());
        try {
            $this->entityManager->persist($result);
        } catch (ORMException $e) {
            throw new ErrorResponseException('Error while persisting mod entity.', 500, $e);
        }
        return $result;
    }

    /**
     * Maps the meta data of the mod.
     * @param ExportMod $exportMod
     * @param DatabaseMod $databaseMod
     */
    protected function mapMetaData(ExportMod $exportMod, DatabaseMod $databaseMod): void
    {
        $databaseMod->setAuthor($exportMod->getAuthor())
                    ->setCurrentVersion($exportMod->getVersion());
    }

    /**
     * Flushes the changes to the database.
     * @throws ErrorResponseException
     */
    protected function flushEntities(): void
    {
        try {
            $this->entityManager->flush();
        } catch (ORMException $e) {
            throw new ErrorResponseException('Error while flushing entities.', 500, $e);
        }
    }
}
