<?php

namespace FactorioItemBrowser\Api\Import\Importer\Mod;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Constant\ModDependencyType;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModDependency as DatabaseDependency;
use FactorioItemBrowser\Api\Database\Entity\ModDependency;
use FactorioItemBrowser\Api\Import\Database\ModService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\Entity\Mod\Dependency as ExportDependency;

/**
 *
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class DependencyImporter extends AbstractImporter implements ModImporterInterface
{
    /**
     * The service of the mods.
     * @var ModService
     */
    protected $modService;

    /**
     * Initializes the importer.
     * @param EntityManager $entityManager
     * @param ModService $modService
     */
    public function __construct(EntityManager $entityManager, ModService $modService)
    {
        parent::__construct($entityManager);
        $this->modService = $modService;
    }

    /**
     * Imports the specified export mod into the database one.
     * @param ExportMod $exportMod
     * @param DatabaseMod $databaseMod
     * @throws ImportException
     */
    public function import(ExportMod $exportMod, DatabaseMod $databaseMod): void
    {
        $newDependencies = $this->getDependenciesFromMod($exportMod, $databaseMod);
        $existingDependencies = $this->getExistingDependencies($newDependencies, $databaseMod);
        $persistedDependencies = $this->persistEntities($newDependencies, $existingDependencies);
        $this->assignEntitiesToCollection($persistedDependencies, $databaseMod->getDependencies());
    }

    /**
     * Returns the dependencies from the specified mod.
     * @param ExportMod $exportMod
     * @param DatabaseMod $databaseMod
     * @return array|DatabaseDependency[]
     * @throws ImportException
     */
    protected function getDependenciesFromMod(ExportMod $exportMod, DatabaseMod $databaseMod): array
    {
        $result = [];
        foreach ($exportMod->getDependencies() as $exportDependency) {
            $databaseDependency = $this->mapDependency($exportDependency, $databaseMod);
            $result[$this->getIdentifier($databaseDependency)] = $databaseDependency;
        }
        return $result;
    }

    /**
     * Maps the specified export dependency to a database entity.
     * @param ExportDependency $exportDependency
     * @param DatabaseMod $databaseMod
     * @return DatabaseDependency
     * @throws ImportException
     */
    protected function mapDependency(ExportDependency $exportDependency, DatabaseMod $databaseMod): DatabaseDependency
    {
        $type = $exportDependency->getIsMandatory() ? ModDependencyType::MANDATORY : ModDependencyType::OPTIONAL;
        $requiredMod = $this->modService->getByName($exportDependency->getRequiredModName());

        $result = new DatabaseDependency($databaseMod, $requiredMod);
        $result->setType($type)
               ->setRequiredVersion($exportDependency->getRequiredVersion());

        return $result;
    }

    /**
     * Returns the already existing entities.
     * @param array|DatabaseDependency[] $newDependencies
     * @param DatabaseMod $databaseMod
     * @return array|DatabaseDependency[]
     */
    protected function getExistingDependencies(array $newDependencies, DatabaseMod $databaseMod): array
    {
        $result = [];
        foreach ($databaseMod->getDependencies() as $dependency) {
            $key = $this->getIdentifier($dependency);
            if (isset($newDependencies[$key])) {
                $this->applyChanges($newDependencies[$key], $dependency);
            }
            $result[$key] = $dependency;
        }
        return $result;
    }

    /**
     * Applies the changes from the source to the destination.
     * @param DatabaseDependency $source
     * @param DatabaseDependency $destination
     */
    protected function applyChanges(DatabaseDependency $source, DatabaseDependency $destination): void
    {
        $destination->setType($source->getType())
                    ->setRequiredVersion($destination->getType());
    }

    /**
     * Returns the identifier of the specified dependency.
     * @param ModDependency $dependency
     * @return string
     */
    protected function getIdentifier(DatabaseDependency $dependency): string
    {
        return $dependency->getRequiredMod()->getName();
    }
}
