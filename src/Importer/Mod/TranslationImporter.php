<?php

namespace FactorioItemBrowser\Api\Import\Importer\Mod;

use FactorioItemBrowser\Api\Database\Constant\TranslationType;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The importer of the mod translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class TranslationImporter extends AbstractImporter implements ModImporterInterface
{
    /**
     * The base combination of the mod.
     * @var DatabaseCombination
     */
    protected $baseCombination;

    /**
     * The translations read from the mod.
     * @var array|Translation[]
     */
    protected $translations = [];

    /**
     * Imports the specified export mod into the database one.
     * @param ExportMod $exportMod
     * @param DatabaseMod $databaseMod
     * @throws ImportException
     */
    public function import(ExportMod $exportMod, DatabaseMod $databaseMod): void
    {
        $baseCombination = $this->findBaseCombination($databaseMod);
        $newTranslations = $this->getTranslationsFromMod($exportMod, $baseCombination);
        $existingTranslations = $this->getExistingTranslations($newTranslations, $baseCombination);
        $persistedTranslations = $this->persistEntities($newTranslations, $existingTranslations);
        $this->assignEntitiesToCollection($persistedTranslations, $baseCombination->getTranslations());
    }

    /**
     * @param DatabaseMod $databaseMod
     * @return DatabaseCombination
     * @throws ImportException
     */
    protected function findBaseCombination(DatabaseMod $databaseMod): DatabaseCombination
    {
        $result = null;
        foreach ($databaseMod->getCombinations() as $databaseCombination) {
            if (count($databaseCombination->getOptionalModIds()) === 0) {
                $result = $databaseCombination;
                break;
            }
        }

        if ($result === null) {
            throw new ImportException('Base combination of mod ' . $databaseMod->getName() . ' not found.');
        }
        return $result;
    }

    /**
     * Returns the translations of the spewcified mod.
     * @param ExportMod $exportMod
     * @param DatabaseCombination $baseCombination
     * @return array|Translation[]
     */
    protected function getTranslationsFromMod(
        ExportMod $exportMod,
        DatabaseCombination $baseCombination
    ): array {
        $this->baseCombination = $baseCombination;
        $this->translations = [];

        $this->copyNotRelatedTranslations($baseCombination);
        $this->processMod($exportMod);

        return $this->translations;
    }

    /**
     * Copies the existing translations which are not related to this importer.
     * @param DatabaseCombination $baseCombination
     */
    protected function copyNotRelatedTranslations(DatabaseCombination $baseCombination): void
    {
        foreach ($baseCombination->getTranslations() as $translation) {
            if ($translation->getType() !== TranslationType::MOD) {
                $this->translations[$this->getIdentifierOfTranslation($translation)] = $translation;
            }
        }
    }

    /**
     * Processes the translations of the specified machine.
     * @param ExportMod $exportMod
     */
    protected function processMod(ExportMod $exportMod): void
    {
        foreach ($exportMod->getTitles()->getTranslations() as $locale => $label) {
            $translation = $this->getTranslation($locale, $exportMod->getName());
            $translation->setValue($label);
        }

        foreach ($exportMod->getDescriptions()->getTranslations() as $locale => $description) {
            $translation = $this->getTranslation($locale, $exportMod->getName());
            $translation->setValue($description);
        }
    }

    /**
     * Returns the translation for the specified values.
     * @param string $locale
     * @param string $name
     * @return Translation
     */
    protected function getTranslation(string $locale, string $name): Translation
    {
        $key = $this->getIdentifier($locale, TranslationType::MOD, $name);
        if (!isset($this->translations[$key])) {
            $this->translations[$key] = new Translation($this->baseCombination, $locale, TranslationType::MOD, $name);
        }
        return $this->translations[$key];
    }

    /**
     * Returns the already existing entities.
     * @param array|Translation[] $newTranslations
     * @param DatabaseCombination $baseCombination
     * @return array|Translation[]
     */
    protected function getExistingTranslations(array $newTranslations, DatabaseCombination $baseCombination): array
    {
        $result = [];
        foreach ($baseCombination->getTranslations() as $translation) {
            $key = $this->getIdentifierOfTranslation($translation);
            if (isset($newTranslations[$key])) {
                $this->applyChanges($newTranslations[$key], $translation);
            }
            $result[$key] = $translation;
        }
        return $result;
    }

    /**
     * Applies the changes from the source to the destination.
     * @param Translation $source
     * @param Translation $destination
     */
    protected function applyChanges(Translation $source, Translation $destination): void
    {
        $destination->setValue($source->getValue())
                    ->setDescription($source->getDescription())
                    ->setIsDuplicatedByMachine($source->getIsDuplicatedByMachine())
                    ->setIsDuplicatedByRecipe($source->getIsDuplicatedByRecipe());
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
