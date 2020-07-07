<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\Common\Constant\RecipeMode;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the recipe translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractTranslationImporter<Recipe>
 */
class RecipeTranslationImporter extends AbstractTranslationImporter
{
    protected function getExportEntities(ExportData $exportData): Generator
    {
        foreach ($exportData->getCombination()->getRecipes() as $recipe) {
            if ($recipe->getMode() === RecipeMode::NORMAL) {
                yield $recipe;
            }
        }
    }

    /**
     * @param ExportData $exportData
     * @param Recipe $recipe
     * @return array<Translation>|Translation[]
     */
    protected function createTranslationsForEntity(ExportData $exportData, $recipe): array
    {
        $translations = $this->createTranslationsFromLocalisedStrings(
            EntityType::RECIPE,
            $recipe->getName(),
            $recipe->getLabels(),
            $recipe->getDescriptions(),
        );

        $translations = $this->filterDuplicatesToItems($translations, array_values(array_filter([
            $this->findItem($exportData, EntityType::ITEM, $recipe->getName()),
            $this->findItem($exportData, EntityType::FLUID, $recipe->getName()),
        ])));

        return $translations;
    }
}
