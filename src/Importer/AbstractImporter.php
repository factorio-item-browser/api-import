<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;

/**
 * The abstract class of the importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
abstract class AbstractImporter implements ImporterInterface
{
    /**
     * The entity manager.
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Initializes the importer.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Persists the specified items into the database.
     * @param array|object[] $newEntities
     * @param array|object[] $existingEntities
     * @return array|object[]
     * @throws ImportException
     */
    protected function persistEntities(array $newEntities, array $existingEntities): array
    {
        $result = [];
        try {
            foreach ($newEntities as $key => $newEntity) {
                if (isset($existingEntities[$key])) {
                    $result[$key] = $existingEntities[$key];
                } else {
                    $this->entityManager->persist($newEntity);
                    $result[$key] = $newEntity;
                }
            }
            $this->entityManager->flush();
        } catch (ORMException $e) {
            throw new ImportException('Failed to persist entities.', 0, $e);
        }
        return $result;
    }

    /**
     * Assigns the items to the database combination.
     * @param array|object[] $entities
     * @param Collection $collection
     */
    protected function assignEntitiesToCollection(array $entities, Collection $collection): void
    {
        $collection->clear();
        foreach ($entities as $entity) {
            $collection->add($entity);
        }
    }
}
