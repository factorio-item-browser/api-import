<?php

namespace FactorioItemBrowser\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Constant\TranslationType;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The importer of the translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class TranslationImporter extends AbstractImporter implements CombinationImporterInterface
{
    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * The database combination.
     * @var DatabaseCombination
     */
    protected $databaseCombination;

    /**
     * The translations read from the combination.
     * @var array|Translation[]
     */
    protected $translations = [];

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
        $existingTranslations = $this->getExistingTranslations($databaseCombination);
        $persistedTranslations = $this->persistEntities($newTranslations, $existingTranslations);
        $this->assignEntitiesToCollection($persistedTranslations, $databaseCombination->getTranslations());
    }

    /**
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @return array|Translation[]
     * @throws ImportException
     */
    protected function getTranslationsFromCombination(
        ExportCombination $exportCombination,
        DatabaseCombination $databaseCombination
    ): array {
        $this->databaseCombination = $databaseCombination;
        $this->translations = [];

        $this->copyMetaTranslations($databaseCombination);
        foreach ($exportCombination->getItemHashes() as $itemHash) {
            $this->processItem($this->registryService->getItem($itemHash));
        }
        foreach ($exportCombination->getMachineHashes() as $machineHash) {
            $this->processMachine($this->registryService->getMachine($machineHash));
        }
        foreach ($exportCombination->getRecipeHashes() as $recipeHash) {
            $this->processRecipe($this->registryService->getRecipe($recipeHash));
        }

        return $this->translations;
    }

    /**
     * Copies the meta translations so they will not get lost while processing the current combination.
     * @param DatabaseCombination $databaseCombination
     */
    protected function copyMetaTranslations(DatabaseCombination $databaseCombination): void
    {
        foreach ($databaseCombination->getTranslations() as $translation) {
            if ($translation->getType() === TranslationType::MOD) {
                $this->translations[$this->getIdentifierOfTranslation($translation)] = $translation;
            }
        }
    }

    /**
     * Processes the translations of the specified item.
     * @param ExportItem $exportItem
     */
    protected function processItem(ExportItem $exportItem): void
    {
        foreach ($exportItem->getLabels()->getTranslations() as $locale => $label) {
            $translation = $this->getTranslation($locale, $exportItem->getType(), $exportItem->getName());
            $translation->setValue($label)
                        ->setIsDuplicatedByMachine($exportItem->getProvidesMachineLocalisation())
                        ->setIsDuplicatedByRecipe($exportItem->getProvidesRecipeLocalisation());
        }

        foreach ($exportItem->getDescriptions()->getTranslations() as $locale => $description) {
            $translation = $this->getTranslation($locale, $exportItem->getType(), $exportItem->getName());
            $translation->setDescription($description)
                        ->setIsDuplicatedByMachine($exportItem->getProvidesMachineLocalisation())
                        ->setIsDuplicatedByRecipe($exportItem->getProvidesRecipeLocalisation());
        }
    }

    /**
     * Processes the translations of the specified machine.
     * @param ExportMachine $exportMachine
     */
    protected function processMachine(ExportMachine $exportMachine): void
    {
        foreach ($exportMachine->getLabels()->getTranslations() as $locale => $label) {
            $translation = $this->getTranslation($locale, TranslationType::MACHINE, $exportMachine->getName());
            $translation->setValue($label);
        }

        foreach ($exportMachine->getDescriptions()->getTranslations() as $locale => $description) {
            $translation = $this->getTranslation($locale, TranslationType::MACHINE, $exportMachine->getName());
            $translation->setValue($description);
        }
    }

    /**
     * Processes the translations of the specified recipe.
     * @param ExportRecipe $exportRecipe
     */
    protected function processRecipe(ExportRecipe $exportRecipe): void
    {
        foreach ($exportRecipe->getLabels()->getTranslations() as $locale => $label) {
            $translation = $this->getTranslation($locale, TranslationType::RECIPE, $exportRecipe->getName());
            $translation->setValue($label);
        }

        foreach ($exportRecipe->getDescriptions()->getTranslations() as $locale => $description) {
            $translation = $this->getTranslation($locale, TranslationType::RECIPE, $exportRecipe->getName());
            $translation->setValue($description);
        }
    }

    /**
     * Returns the translation for the specified values.
     * @param string $locale
     * @param string $type
     * @param string $name
     * @return Translation
     */
    protected function getTranslation(string $locale, string $type, string $name): Translation
    {
        $key = $this->getIdentifier($locale, $type, $name);
        if (!isset($this->translations[$key])) {
            $this->translations[$key] = new Translation($this->databaseCombination, $locale, $type, $name);
        }
        return $this->translations[$key];
    }

    /**
     * Returns the already existing entities.
     * @param DatabaseCombination $databaseCombination
     * @return array
     */
    protected function getExistingTranslations(DatabaseCombination $databaseCombination): array
    {
        $result = [];
        foreach ($databaseCombination->getTranslations() as $translation) {
            $result[$this->getIdentifierOfTranslation($translation)] = $translation;
        }
        return $result;
    }

    /**
     * Returns the identifier for the specified values.
     * @param string $locale
     * @param string $type
     * @param string $name
     * @return string
     */
    protected function getIdentifier(string $locale, string $type, string $name): string
    {
        return EntityUtils::buildIdentifier([$locale, $type, $name]);
    }

    /**
     * Returns the identifier of the specified translation.
     * @param Translation $translation
     * @return string
     */
    protected function getIdentifierOfTranslation(Translation $translation): string
    {
        return $this->getIdentifier($translation->getLocale(), $translation->getType(), $translation->getName());
    }
}
