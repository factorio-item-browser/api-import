<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Common\Constant\RecipeMode;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the item translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractTranslationImporter<Item>
 */
class ItemTranslationImporter extends AbstractTranslationImporter
{
    protected function getExportEntities(ExportData $exportData): Generator
    {
        yield from $exportData->getCombination()->getItems();
    }

    /**
     * @param ExportData $exportData
     * @param Item $item
     * @return array<Translation>|Translation[]
     */
    protected function createTranslationsForEntity(ExportData $exportData, $item): array
    {
        $translations = $this->createTranslationsFromLocalisedStrings(
            $item->getType(),
            $item->getName(),
            $item->getLabels(),
            $item->getDescriptions(),
        );

        $this->checkRecipeDuplication($translations, $this->findRecipe($exportData, $item->getName()));
        $this->checkMachineDuplication($translations, $this->findMachine($exportData, $item->getName()));

        return $translations;
    }

    protected function findRecipe(ExportData $exportData, string $name): ?Recipe
    {
        foreach ($exportData->getCombination()->getRecipes() as $recipe) {
            if ($recipe->getName() === $name && $recipe->getMode() === RecipeMode::NORMAL) {
                return $recipe;
            }
        }
        return null;
    }

    /**
     * @param array<Translation>|Translation[] $translations
     * @param Recipe|null $recipe
     */
    protected function checkRecipeDuplication(array $translations, ?Recipe $recipe): void
    {
        if ($recipe === null) {
            return;
        }

        foreach ($translations as $translation) {
            $label = $recipe->getLabels()->getTranslations()[$translation->getLocale()] ?? '';
            $description = $recipe->getDescriptions()->getTranslations()[$translation->getLocale()] ?? '';

            if (
                ($label === $translation->getValue())
                && ($description === '' || $description === $translation->getDescription())
            ) {
                $translation->setIsDuplicatedByRecipe(true);
            }
        }
    }

    protected function findMachine(ExportData $exportData, string $name): ?Machine
    {
        foreach ($exportData->getCombination()->getMachines() as $machine) {
            if ($machine->getName() === $name) {
                return $machine;
            }
        }
        return null;
    }

    /**
     * @param array<Translation>|Translation[] $translations
     * @param Machine|null $machine
     */
    protected function checkMachineDuplication(array $translations, ?Machine $machine): void
    {
        if ($machine === null) {
            return;
        }

        foreach ($translations as $translation) {
            $locale = $translation->getLocale();
            $label = $machine->getLabels()->getTranslations()[$locale] ?? '';
            $description = $machine->getDescriptions()->getTranslations()[$locale] ?? '';

            if (
                ($label === $translation->getValue())
                && ($description === '' || $description === $translation->getDescription())
            ) {
                $translation->setIsDuplicatedByMachine(true);
            }
        }
    }
}
