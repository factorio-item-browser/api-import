<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Mod;

use FactorioItemBrowser\Api\Database\Constant\TranslationType;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Api\Import\Importer\AbstractTranslationImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;

/**
 * The importer of the mod translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class TranslationImporter extends AbstractTranslationImporter implements ModImporterInterface
{
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
     * Returns the translations of the specified mod.
     * @param ExportMod $exportMod
     * @param DatabaseCombination $baseCombination
     * @return array|Translation[]
     */
    protected function getTranslationsFromMod(
        ExportMod $exportMod,
        DatabaseCombination $baseCombination
    ): array {
        $translationAggregator = $this->createTranslationAggregator($baseCombination);

        $this->copyNotRelatedTranslations($translationAggregator, $baseCombination);
        $this->processMod($translationAggregator, $exportMod);

        return $translationAggregator->getAggregatedTranslations();
    }

    /**
     * Copies the existing translations which are not related to this importer.
     * @param TranslationAggregator $translationAggregator
     * @param DatabaseCombination $baseCombination
     */
    protected function copyNotRelatedTranslations(
        TranslationAggregator $translationAggregator,
        DatabaseCombination $baseCombination
    ): void {
        foreach ($baseCombination->getTranslations() as $translation) {
            if ($translation->getType() !== TranslationType::MOD) {
                $translationAggregator->addTranslation($translation);
            }
        }
    }

    /**
     * Processes the translations of the specified machine.
     * @param TranslationAggregator $translationAggregator
     * @param ExportMod $exportMod
     */
    protected function processMod(TranslationAggregator $translationAggregator, ExportMod $exportMod): void
    {
        $translationAggregator->applyLocalisedStringToValue(
            $exportMod->getTitles(),
            TranslationType::MOD,
            $exportMod->getName()
        );
        $translationAggregator->applyLocalisedStringToDescription(
            $exportMod->getDescriptions(),
            TranslationType::MOD,
            $exportMod->getName()
        );
    }
}
