<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\NewImporter;

use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the machine translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractTranslationImporter<Machine>
 */
class MachineTranslationImporter extends AbstractTranslationImporter
{
    protected function getExportEntities(ExportData $exportData): Generator
    {
        yield from $exportData->getCombination()->getMachines();
    }

    /**
     * @param ExportData $exportData
     * @param Machine $machine
     * @return array<Translation>|Translation[]
     */
    protected function createTranslationsForEntity(ExportData $exportData, $machine): array
    {
        $translations = $this->createTranslationsFromLocalisedStrings(
            EntityType::MACHINE,
            $machine->getName(),
            $machine->getLabels(),
            $machine->getDescriptions(),
        );

        $translations = $this->filterDuplicatesToItems($translations, array_values(array_filter([
            $this->findItem($exportData, EntityType::ITEM, $machine->getName()),
        ])));

        return $translations;
    }
}
