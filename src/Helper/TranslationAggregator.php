<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Helper;

use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\ExportData\Entity\LocalisedString;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The class helping with aggregating translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class TranslationAggregator
{
    /**
     * The combination to assign to the translations.
     * @var ModCombination
     */
    protected $combination;

    /**
     * The aggregated translations.
     * @var array|Translation[]
     */
    protected $translations = [];

    /**
     * Initializes the aggregator.
     * @param ModCombination $combination
     */
    public function __construct(ModCombination $combination)
    {
        $this->combination = $combination;
    }

    /**
     * Adds an actual translation entity to the aggregator.
     * @param Translation $translation
     */
    public function addTranslation(Translation $translation): void
    {
        $this->translations[self::getIdentifierOfTranslation($translation)] = $translation;
    }

    /**
     * Applies the localised string to the value of the respective translations.
     * @param LocalisedString $localisedString
     * @param string $type
     * @param string $name
     */
    public function applyLocalisedStringToValue(LocalisedString $localisedString, string $type, string $name): void
    {
        foreach ($localisedString->getTranslations() as $locale => $value) {
            $translation = $this->getTranslation($locale, $type, $name);
            $translation->setValue($value);
        }
    }

    /**
     * Applies the localised string to the description of the respective translations.
     * @param LocalisedString $localisedString
     * @param string $type
     * @param string $name
     */
    public function applyLocalisedStringToDescription(
        LocalisedString $localisedString,
        string $type,
        string $name
    ): void {
        foreach ($localisedString->getTranslations() as $locale => $value) {
            $translation = $this->getTranslation($locale, $type, $name);
            $translation->setDescription($value);
        }
    }

    /**
     * Applies the localised string to the duplication flags of the respective translations.
     * @param LocalisedString $localisedString
     * @param string $type
     * @param string $name
     * @param bool $isDuplicatedByMachine
     * @param bool $isDuplicatedByRecipe
     */
    public function applyLocalisedStringToDuplicationFlags(
        LocalisedString $localisedString,
        string $type,
        string $name,
        bool $isDuplicatedByMachine,
        bool $isDuplicatedByRecipe
    ): void {
        foreach ($localisedString->getTranslations() as $locale => $value) {
            $translation = $this->getTranslation($locale, $type, $name);
            $translation->setIsDuplicatedByMachine($isDuplicatedByMachine)
                        ->setIsDuplicatedByRecipe($isDuplicatedByRecipe);
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
        $key = self::getIdentifier($locale, $type, $name);
        if (!isset($this->translations[$key])) {
            $this->translations[$key] = $this->createTranslation($locale, $type, $name);
        }
        return $this->translations[$key];
    }

    /**
     * Creates a new translation entity.
     * @param string $locale
     * @param string $type
     * @param string $name
     * @return Translation
     */
    protected function createTranslation(string $locale, string $type, string $name): Translation
    {
        return new Translation($this->combination, $locale, $type, $name);
    }

    /**
     * Returns the aggregated translations.
     * @return array|Translation[]
     */
    public function getAggregatedTranslations(): array
    {
        return $this->translations;
    }

    /**
     * Returns the identifier for the specified values.
     * @param string $locale
     * @param string $type
     * @param string $name
     * @return string
     */
    public static function getIdentifier(string $locale, string $type, string $name): string
    {
        return EntityUtils::buildIdentifier([$locale, $type, $name]);
    }

    /**
     * Returns the identifier of the specified translation.
     * @param Translation $translation
     * @return string
     */
    public static function getIdentifierOfTranslation(Translation $translation): string
    {
        return self::getIdentifier($translation->getLocale(), $translation->getType(), $translation->getName());
    }
}
