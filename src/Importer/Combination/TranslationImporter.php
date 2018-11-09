<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Constant\TranslationType;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Api\Import\Importer\AbstractTranslationImporter;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;

/**
 * The importer of the translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class TranslationImporter extends AbstractTranslationImporter implements CombinationImporterInterface
{
    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * Initializes the importer.
     * @param EntityManager $entityManager
     * @param RegistryService $registryService
     */
    public function __construct(EntityManager $entityManager, RegistryService $registryService)
    {
        parent::__construct($entityManager);
        $this->registryService = $registryService;
    }

    /**
     * Imports the items.
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @throws ImportException
     */
    public function import(ExportCombination $exportCombination, DatabaseCombination $databaseCombination): void
    {
        $newTranslations = $this->getTranslationsFromCombination($exportCombination, $databaseCombination);
        $existingTranslations = $this->getExistingTranslations($newTranslations, $databaseCombination);
        $persistedTranslations = $this->persistEntities($newTranslations, $existingTranslations);
        $this->assignEntitiesToCollection($persistedTranslations, $databaseCombination->getTranslations());
    }

    /**
     * Returns the translations from the specified combination.
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @return array|Translation[]
     * @throws ImportException
     */
    protected function getTranslationsFromCombination(
        ExportCombination $exportCombination,
        DatabaseCombination $databaseCombination
    ): array {
        $translationAggregator = $this->createTranslationAggregator($databaseCombination);

        $this->copyNotRelatedTranslations($translationAggregator, $databaseCombination);
        $this->processItems($translationAggregator, $exportCombination->getItemHashes());
        $this->processMachines($translationAggregator, $exportCombination->getMachineHashes());
        $this->processRecipes($translationAggregator, $exportCombination->getRecipeHashes());

        return $translationAggregator->getAggregatedTranslations();
    }

    /**
     * Copies any translation not related to the current import step.
     * @param TranslationAggregator $translationAggregator
     * @param DatabaseCombination $databaseCombination
     */
    protected function copyNotRelatedTranslations(
        TranslationAggregator $translationAggregator,
        DatabaseCombination $databaseCombination
    ): void {
        foreach ($databaseCombination->getTranslations() as $translation) {
            if ($translation->getType() === TranslationType::MOD) {
                $translationAggregator->addTranslation($translation);
            }
        }
    }

    /**
     * Processes the translations of the specified item.
     * @param TranslationAggregator $translationAggregator
     * @param array|string[] $itemHashes
     * @throws ImportException
     */
    protected function processItems(TranslationAggregator $translationAggregator, array $itemHashes): void
    {
        foreach ($itemHashes as $itemHash) {
            $exportItem = $this->registryService->getItem($itemHash);
            
            $translationAggregator->applyLocalisedStringToValue(
                $exportItem->getLabels(),
                $exportItem->getType(),
                $exportItem->getName()
            );
            $translationAggregator->applyLocalisedStringToDescription(
                $exportItem->getDescriptions(),
                $exportItem->getType(),
                $exportItem->getName()
            );

            $translationAggregator->applyLocalisedStringToDuplicationFlags(
                $exportItem->getLabels(),
                $exportItem->getType(),
                $exportItem->getName(),
                $exportItem->getProvidesMachineLocalisation(),
                $exportItem->getProvidesRecipeLocalisation()
            );
            $translationAggregator->applyLocalisedStringToDuplicationFlags(
                $exportItem->getDescriptions(),
                $exportItem->getType(),
                $exportItem->getName(),
                $exportItem->getProvidesMachineLocalisation(),
                $exportItem->getProvidesRecipeLocalisation()
            );
        }
    }

    /**
     * Processes the translations of the specified machine.
     * @param TranslationAggregator $translationAggregator
     * @param array|string[] $machineHashes
     * @throws ImportException
     */
    protected function processMachines(TranslationAggregator $translationAggregator, array $machineHashes): void
    {
        foreach ($machineHashes as $machineHash) {
            $exportMachine = $this->registryService->getMachine($machineHash);
            
            $translationAggregator->applyLocalisedStringToValue(
                $exportMachine->getLabels(),
                TranslationType::MACHINE,
                $exportMachine->getName()
            );
            $translationAggregator->applyLocalisedStringToDescription(
                $exportMachine->getDescriptions(),
                TranslationType::MACHINE,
                $exportMachine->getName()
            );
        }
    }

    /**
     * Processes the translations of the specified recipe.
     * @param TranslationAggregator $translationAggregator
     * @param array|string[] $recipeHashes
     * @throws ImportException
     */
    protected function processRecipes(TranslationAggregator $translationAggregator, array $recipeHashes): void
    {
        foreach ($recipeHashes as $recipeHash) {
            $exportRecipe = $this->registryService->getRecipe($recipeHash);
            
            $translationAggregator->applyLocalisedStringToValue(
                $exportRecipe->getLabels(),
                TranslationType::RECIPE,
                $exportRecipe->getName()
            );
            $translationAggregator->applyLocalisedStringToDescription(
                $exportRecipe->getDescriptions(),
                TranslationType::RECIPE,
                $exportRecipe->getName()
            );
        }
    }
}
