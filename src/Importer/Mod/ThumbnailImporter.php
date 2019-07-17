<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Mod;

use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconFile;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\AbstractIconImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;

/**
 * The importer for the mod thumbnail.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ThumbnailImporter extends AbstractIconImporter implements ModImporterInterface
{
    /**
     * Imports the specified export mod into the database one.
     * @param ExportMod $exportMod
     * @param DatabaseMod $databaseMod
     * @throws ImportException
     */
    public function import(ExportMod $exportMod, DatabaseMod $databaseMod): void
    {
        if ($exportMod->getThumbnailHash() !== '') {
            $iconFile = $this->fetchIconFile($exportMod->getThumbnailHash());
            $this->processIconFile($databaseMod, $iconFile);
            $this->flushEntities();
        }
    }

    /**
     * Processes the icon file as thumbnail for the mod.
     * @param DatabaseMod $databaseMod
     * @param IconFile $iconFile
     * @throws ImportException
     */
    protected function processIconFile(DatabaseMod $databaseMod, IconFile $iconFile): void
    {
        $baseCombination = $this->getBaseCombination($databaseMod);
        $icon = $this->getExistingThumbnailIcon($baseCombination);

        if ($icon !== null) {
            $icon->setFile($iconFile);
        } else {
            $icon = $this->createIcon($baseCombination, $iconFile, EntityType::MOD, $databaseMod->getName());
            $baseCombination->getIcons()->add($icon);
            $this->persistEntity($icon);
        }
    }

    /**
     * Returns the base combination from the mod.
     * @param DatabaseMod $databaseMod
     * @return DatabaseCombination
     * @throws ImportException
     */
    protected function getBaseCombination(DatabaseMod $databaseMod): DatabaseCombination
    {
        foreach ($databaseMod->getCombinations() as $combination) {
            if (count($combination->getOptionalModIds()) === 0) {
                return $combination;
            }
        }

        throw new ImportException('Base combination is missing on mod ' . $databaseMod->getName());
    }

    /**
     * Returns the existing thumbnail icon from the combination.
     * @param DatabaseCombination $baseCombination
     * @return DatabaseIcon|null
     */
    protected function getExistingThumbnailIcon(DatabaseCombination $baseCombination): ?DatabaseIcon
    {
        foreach ($baseCombination->getIcons() as $icon) {
            if ($icon->getType() === EntityType::MOD) {
                return $icon;
            }
        }

        return null;
    }
}
