<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;

/**
 * The abstract class of the translation importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
abstract class AbstractTranslationImporter extends AbstractImporter
{
    /**
     * Creates and returns the translation aggregator.
     * @param DatabaseCombination $combination
     * @return TranslationAggregator
     */
    protected function createTranslationAggregator(ModCombination $combination): TranslationAggregator
    {
        return new TranslationAggregator($combination);
    }

    /**
     * Returns the already existing entities.
     * @param array|Translation[] $newTranslations
     * @param DatabaseCombination $databaseCombination
     * @return array|Translation[]
     */
    protected function getExistingTranslations(array $newTranslations, DatabaseCombination $databaseCombination): array
    {
        $result = [];
        foreach ($databaseCombination->getTranslations() as $translation) {
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
     * Returns the identifier of the specified translation.
     * @param Translation $translation
     * @return string
     */
    protected function getIdentifierOfTranslation(Translation $translation): string
    {
        return TranslationAggregator::getIdentifierOfTranslation($translation);
    }
}
