<?php

namespace FactorioItemBrowser\Api\Import\Importer\Mod;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Import\Database\ModService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;

/**
 * The importer for adding the basic combination entities.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CombinationImporter extends AbstractImporter implements ModImporterInterface
{
    /**
     * The service of the mods.
     * @var ModService
     */
    protected $modService;

    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * Initializes the importer.
     * @param EntityManager $entityManager
     * @param ModService $modService
     * @param RegistryService $registryService
     */
    public function __construct(EntityManager $entityManager, ModService $modService, RegistryService $registryService)
    {
        parent::__construct($entityManager);
        $this->modService = $modService;
        $this->registryService = $registryService;
    }

    /**
     * Imports the specified export mod into the database one.
     * @param ExportMod $exportMod
     * @param DatabaseMod $databaseMod
     * @throws ImportException
     */
    public function import(ExportMod $exportMod, DatabaseMod $databaseMod): void
    {
        $newCombinations = $this->getCombinationsFromMod($exportMod, $databaseMod);
        $existingCombinations = $this->getExistingCombinations($newCombinations, $databaseMod);
        $persistedCombinations = $this->persistEntities($newCombinations, $existingCombinations);
        $this->assignEntitiesToCollection($persistedCombinations, $databaseMod->getCombinations());
    }

    /**
     * Returns the combinations from the specified mod.
     * @param ExportMod $exportMod
     * @param DatabaseMod $databaseMod
     * @return array|DatabaseCombination[]
     * @throws ImportException
     */
    protected function getCombinationsFromMod(ExportMod $exportMod, DatabaseMod $databaseMod): array
    {
        $result = [];
        foreach ($exportMod->getCombinationHashes() as $combinationHash) {
            $exportCombination = $this->registryService->getCombination($combinationHash);
            $databaseCombination = $this->mapCombination($exportCombination, $databaseMod);
            $result[$this->getIdentifier($databaseCombination)] = $databaseCombination;
        }
        return $result;
    }

    /**
     * Maps the export combination to a database entity.
     * @param ExportCombination $exportCombination
     * @param DatabaseMod $databaseMod
     * @return DatabaseCombination
     * @throws ImportException
     */
    protected function mapCombination(
        ExportCombination $exportCombination,
        DatabaseMod $databaseMod
    ): DatabaseCombination {
        $optionalModIds = [];
        foreach ($exportCombination->getLoadedOptionalModNames() as $optionalModName) {
            $optionalMod = $this->modService->getByName($optionalModName);
            $optionalModIds[] = $optionalMod->getId();
        }
        sort($optionalModIds);

        $result = new DatabaseCombination($databaseMod, $exportCombination->getName());
        $result->setOptionalModIds($optionalModIds);
        return $result;
    }

    /**
     * Returns the already existing entities.
     * @param array|DatabaseCombination[] $newCombinations
     * @param DatabaseMod $databaseMod
     * @return array|DatabaseCombination[]
     */
    protected function getExistingCombinations(array $newCombinations, DatabaseMod $databaseMod): array
    {
        $result = [];
        foreach ($databaseMod->getCombinations() as $existingCombination) {
            $key = $this->getIdentifier($existingCombination);
            if (isset($newCombinations[$key])) {
                $this->applyChanges($newCombinations[$key], $existingCombination);
            }
            $result[$key] = $existingCombination;
        }
        return $result;
    }

    /**
     * Applies the changes of the source combination to the destination one.
     * @param DatabaseCombination $source
     * @param DatabaseCombination $destination
     */
    protected function applyChanges(DatabaseCombination $source, DatabaseCombination $destination): void
    {
        $destination->setOptionalModIds($source->getOptionalModIds());
    }

    /**
     * Returns the identifier of the specified combination.
     * @param DatabaseCombination $combination
     * @return string
     */
    protected function getIdentifier(DatabaseCombination $combination): string
    {
        return $combination->getName();
    }
}
