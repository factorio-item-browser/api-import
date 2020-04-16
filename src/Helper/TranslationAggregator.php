<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Helper;

use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\LocalisedString;

/**
 * The class helping with aggregating translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class TranslationAggregator
{
    /**
     * The aggregated translations.
     * @var array|Translation[][][]
     */
    protected $translations = [];

    /**
     * Adds translations to the aggregator.
     * @param string $type
     * @param string $name
     * @param LocalisedString $values
     * @param LocalisedString $descriptions
     * @return $this
     */
    public function add(string $type, string $name, LocalisedString $values, LocalisedString $descriptions): self
    {
        foreach ($values->getTranslations() as $locale => $value) {
            $this->createTranslation($locale, $type, $name)->setValue($value);
        }
        foreach ($descriptions->getTranslations() as $locale => $description) {
            $this->createTranslation($locale, $type, $name)->setDescription($description);
        }
        return $this;
    }

    /**
     * Returns the translation for the specified values.
     * @param string $locale
     * @param string $type
     * @param string $name
     * @return Translation
     */
    protected function createTranslation(string $locale, string $type, string $name): Translation
    {
        if (!isset($this->translations[$locale][$type][$name])) {
            $translation = new Translation();
            $translation->setLocale($locale)
                        ->setType($type)
                        ->setName($name);
            $this->translations[$locale][$type][$name] = $translation;
        }
        return $this->translations[$locale][$type][$name];
    }

    /**
     * Optimizes the translations.
     * @return $this
     */
    public function optimize(): self
    {
        $this->optimizeType(EntityType::MACHINE, function (Translation $translation): void {
            $translation->setIsDuplicatedByMachine(true);
        });
        $this->optimizeType(EntityType::RECIPE, function (Translation $translation): void {
            $translation->setIsDuplicatedByRecipe(true);
        });

        return $this;
    }

    /**
     * Optimizes a type of translations.
     * @param string $type
     * @param callable $callbackMarkAsDuplicate
     */
    protected function optimizeType(string $type, callable $callbackMarkAsDuplicate): void
    {
        foreach ($this->translations as $locale => $translationsByLocale) {
            foreach ($translationsByLocale[$type] ?? [] as $name => $translation) {
                $duplicatingTranslation = $this->getDuplicatingTranslation($translation);
                if ($duplicatingTranslation !== null) {
                    $callbackMarkAsDuplicate($duplicatingTranslation);
                    unset($this->translations[$locale][$type][$name]);
                }
            }
        }
    }

    /**
     * Returns the translation duplicating the specified one, if there is any.
     * @param Translation $translation
     * @return Translation|null
     */
    protected function getDuplicatingTranslation(Translation $translation): ?Translation
    {
        foreach ([EntityType::ITEM, EntityType::FLUID] as $type) {
            $possibleDuplicate = $this->translations[$translation->getLocale()][$type][$translation->getName()] ?? null;
            if (
                $possibleDuplicate !== null
                && $translation->getValue() === $possibleDuplicate->getValue()
                && ($translation->getDescription() === ''
                    || $translation->getDescription() === $possibleDuplicate->getDescription()
                )
            ) {
                return $possibleDuplicate;
            }
        }
        return null;
    }

    /**
     * Returns the aggregated translations.
     * @return array|Translation[]
     */
    public function getTranslations(): array
    {
        $result = [];
        foreach ($this->translations as $translationsByLocale) {
            foreach ($translationsByLocale as $translationsByType) {
                foreach ($translationsByType as $translation) {
                    $result[] = $translation;
                }
            }
        }
        return $result;
    }
}
