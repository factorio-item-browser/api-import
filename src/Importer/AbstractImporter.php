<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FactorioItemBrowser\Api\Import\Exception\ImportException;

/**
 * The abstract class of the importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
abstract class AbstractImporter
{
    /**
     * The entity manager.
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Initializes the importer.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Persists the specified entities into the database.
     * @param array|object[] $newEntities
     * @param array|object[] $existingEntities
     * @return array|object[]
     * @throws ImportException
     */
    protected function persistEntities(array $newEntities, array $existingEntities): array
    {
        $result = [];
        foreach ($newEntities as $key => $newEntity) {
            if (isset($existingEntities[$key])) {
                $result[$key] = $existingEntities[$key];
            } else {
                $this->persistEntity($newEntity);
                $result[$key] = $newEntity;
            }
        }

        $this->flushEntities();
        return $result;
    }

    /**
     * Persists the specified entity.
     * @param object $entity
     * @throws ImportException
     */
    protected function persistEntity($entity): void
    {
        try {
            $this->entityManager->persist($entity);
        } catch (Exception $e) {
            throw new ImportException('Failed to persist entity.', 0, $e);
        }
    }

    /**
     * Flushes the entities to the database.
     * @throws ImportException
     */
    protected function flushEntities(): void
    {
        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new ImportException('Failed to flush entities.', 0, $e);
        }
    }

    /**
     * Assigns the items to the database combination.
     * @param array|object[] $entities
     * @param Collection $collection
     * @throws ImportException
     */
    protected function assignEntitiesToCollection(array $entities, Collection $collection): void
    {
        $collection->clear();
        foreach ($entities as $entity) {
            $collection->add($entity);
        }

        $this->flushEntities();
    }
}
