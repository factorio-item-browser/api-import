<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\DBAL\DBALException;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the mod translations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractTranslationImporter<Mod>
 */
class ModTranslationImporter extends AbstractTranslationImporter
{
    /**
     * @param Combination $combination
     * @throws DBALException
     */
    public function prepare(Combination $combination): void
    {
        $this->repository->clearCrossTable($combination->getId());
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        yield from $exportData->getMods();
    }

    /**
     * @param ExportData $exportData
     * @param Mod $mod
     * @return array<Translation>
     */
    protected function createTranslationsForEntity(ExportData $exportData, $mod): array
    {
        return $this->createTranslationsFromLocalisedStrings(
            EntityType::MOD,
            $mod->name,
            $mod->titles,
            $mod->descriptions,
        );
    }

    public function cleanup(): void
    {
        $this->repository->removeOrphans();
    }
}
