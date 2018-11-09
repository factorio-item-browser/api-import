<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Repository\RepositoryWithOrphansInterface;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;

/**
 * The importer cleaning up any no longer needed data in the database.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CleanupImporter extends AbstractImporter implements GenericImporterInterface
{
    /**
     * The repositories with potential orphans.
     * @var array|RepositoryWithOrphansInterface[]
     */
    protected $repositoriesWithOrphans;

    /**
     * Initializes the importer.
     * @param EntityManager $entityManager
     * @param array|RepositoryWithOrphansInterface[] $repositoriesWithOrphans
     */
    public function __construct(EntityManager $entityManager, array $repositoriesWithOrphans)
    {
        parent::__construct($entityManager);
        $this->repositoriesWithOrphans = $repositoriesWithOrphans;
    }

    /**
     * Imports some generic data.
     * @throws ImportException
     */
    public function import(): void
    {
        foreach ($this->repositoriesWithOrphans as $repository) {
            $repository->removeOrphans();
        }

        $this->flushEntities();
    }
}
