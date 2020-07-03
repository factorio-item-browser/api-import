<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\NewImporter;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\AbstractIdRepository;
use FactorioItemBrowser\Api\Database\Repository\AbstractIdRepositoryWithOrphans;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The abstract importer working with an id repository.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @template TExport
 * @template TDatabase of \FactorioItemBrowser\Api\Database\Entity\EntityWithId
 * @extends AbstractImporter<TExport>
 */
abstract class AbstractEntityImporter extends AbstractImporter
{
    protected EntityManagerInterface $entityManager;

    /**
     * @var AbstractIdRepository<TDatabase>
     */
    protected AbstractIdRepository $repository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param AbstractIdRepository<TDatabase> $repository
     */
    public function __construct(EntityManagerInterface $entityManager, AbstractIdRepository $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    /**
     * Prepares the combination for the import.
     * @param Combination $combination
     */
    public function prepare(Combination $combination): void
    {
        $this->getCollectionFromCombination($combination)->clear();
        $this->entityManager->flush();
    }

    /**
     * Returns the collection from the database which holds the entities.
     * @param Combination $combination
     * @return Collection<int, TDatabase>
     */
    abstract protected function getCollectionFromCombination(Combination $combination): Collection;

    /**
     * Imports the specified chunk of the data from the export data.
     * @param Combination $combination
     * @param ExportData $exportData
     * @param int $offset
     * @param int $limit
     */
    public function import(Combination $combination, ExportData $exportData, int $offset, int $limit): void
    {
        $entities = $this->getDatabaseEntities($exportData, $offset, $limit);
        $entities = $this->fetchExistingEntities($entities);

        $collection = $this->getCollectionFromCombination($combination);
        foreach ($entities as $entity) {
            $collection->add($entity);
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
    }

    /**
     * Returns the database entities of the specified chunk of the export data.
     * @param ExportData $exportData
     * @param int $offset
     * @param int $limit
     * @return array<TDatabase>
     */
    protected function getDatabaseEntities(ExportData $exportData, int $offset, int $limit): array
    {
        $entities = $this->getChunkedExportEntities($exportData, $offset, $limit);
        return array_map([$this, 'createDatabaseEntity'], $entities);
    }

    /**
     * Creates the database entity from the specified export entity.
     * @param TExport $entity
     * @return TDatabase
     */
    abstract protected function createDatabaseEntity($entity);

    /**
     * Fetches already existing entities from the database.
     * @param array<TDatabase> $entities
     * @return array<TDatabase>
     */
    protected function fetchExistingEntities(array $entities): array
    {
        $ids = [];
        $mappedEntities = [];
        foreach ($entities as $entity) {
            $id = $entity->getId();
            $ids[] = $id;
            $mappedEntities[$id->toString()] = $entity;
        }

        $databaseEntities = $this->repository->findByIds($ids);
        foreach ($databaseEntities as $entity) {
            $mappedEntities[$entity->getId()->toString()] = $entity;
        }
        return array_values($mappedEntities);
    }

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void
    {
        if ($this->repository instanceof AbstractIdRepositoryWithOrphans) {
            $this->repository->removeOrphans();
        }
    }
}
