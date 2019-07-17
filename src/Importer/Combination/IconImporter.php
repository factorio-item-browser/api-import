<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Combination;

use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconFile;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\AbstractIconImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The importer of the icons.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class IconImporter extends AbstractIconImporter implements CombinationImporterInterface
{
    /**
     * The database combination.
     * @var DatabaseCombination
     */
    protected $databaseCombination;

    /**
     * The icon files read from the combination.
     * @var array|IconFile[]
     */
    protected $iconFiles = [];

    /**
     * Imports the items.
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @throws ImportException
     */
    public function import(ExportCombination $exportCombination, DatabaseCombination $databaseCombination): void
    {
        $newIcons = $this->getIconsFromCombination($exportCombination, $databaseCombination);
        $existingIcons = $this->getExistingIcons($newIcons, $databaseCombination);
        $persistedIcons = $this->persistEntities($newIcons, $existingIcons);
        $this->assignEntitiesToCollection($persistedIcons, $databaseCombination->getIcons());
    }

    /**
     * Returns the icons from the specified combination.
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @return array|DatabaseIcon[]
     * @throws ImportException
     */
    protected function getIconsFromCombination(
        ExportCombination $exportCombination,
        DatabaseCombination $databaseCombination
    ): array {
        $this->databaseCombination = $databaseCombination;
        $this->iconFiles = [];

        return array_merge(
            $this->getIconsForItems($exportCombination),
            $this->getIconsForMachines($exportCombination),
            $this->getIconsForRecipes($exportCombination)
        );
    }

    /**
     * Returns the icons of the items for the specified combination.
     * @param ExportCombination $exportCombination
     * @return array
     * @throws ImportException
     */
    protected function getIconsForItems(ExportCombination $exportCombination): array
    {
        $result = [];
        foreach ($exportCombination->getItemHashes() as $itemHash) {
            $item = $this->registryService->getItem($itemHash);
            if ($item->getIconHash() !== '') {
                $iconFile = $this->getIconFile($item->getIconHash());
                $icon = $this->createIcon(
                    $this->databaseCombination,
                    $iconFile,
                    $item->getType(),
                    $item->getName()
                );
                $result[$this->getIdentifier($icon)] = $icon;
            }
        }
        return $result;
    }

    /**
     * Returns the icons of the recipes for the specified combination.
     * @param ExportCombination $exportCombination
     * @return array
     * @throws ImportException
     */
    protected function getIconsForRecipes(ExportCombination $exportCombination): array
    {
        $result = [];
        foreach ($exportCombination->getRecipeHashes() as $recipeHash) {
            $recipe = $this->registryService->getRecipe($recipeHash);
            if ($recipe->getIconHash() !== '') {
                $iconFile = $this->getIconFile($recipe->getIconHash());
                $icon = $this->createIcon(
                    $this->databaseCombination,
                    $iconFile,
                    EntityType::RECIPE,
                    $recipe->getName()
                );
                $result[$this->getIdentifier($icon)] = $icon;
            }
        }
        return $result;
    }

    /**
     * Returns the icons of the machines for the specified combination.
     * @param ExportCombination $exportCombination
     * @return array
     * @throws ImportException
     */
    protected function getIconsForMachines(ExportCombination $exportCombination): array
    {
        $result = [];
        foreach ($exportCombination->getMachineHashes() as $machineHash) {
            $machine = $this->registryService->getMachine($machineHash);
            if ($machine->getIconHash() !== '') {
                $iconFile = $this->getIconFile($machine->getIconHash());
                $icon = $this->createIcon(
                    $this->databaseCombination,
                    $iconFile,
                    EntityType::MACHINE,
                    $machine->getName()
                );
                $result[$this->getIdentifier($icon)] = $icon;
            }
        }
        return $result;
    }

    /**
     * Returns the icon file with the specified hash.
     * @param string $iconHash
     * @return IconFile
     * @throws ImportException
     */
    protected function getIconFile(string $iconHash): IconFile
    {
        if (!isset($this->iconFiles[$iconHash])) {
            $this->iconFiles[$iconHash] = $this->fetchIconFile($iconHash);
        }
        return $this->iconFiles[$iconHash];
    }

    /**
     * Returns the already existing entities.
     * @param array|DatabaseIcon[] $newIcons
     * @param DatabaseCombination $databaseCombination
     * @return array|DatabaseIcon[]
     */
    protected function getExistingIcons(array $newIcons, DatabaseCombination $databaseCombination): array
    {
        $result = [];
        foreach ($databaseCombination->getIcons() as $icon) {
            $key = $this->getIdentifier($icon);
            if (isset($newIcons[$key])) {
                $this->applyChanges($newIcons[$key], $icon);
            }
            $result[$key] = $icon;
        }
        return $result;
    }

    /**
     * Applies the changes from the source to the destination.
     * @param DatabaseIcon $source
     * @param DatabaseIcon $destination
     */
    protected function applyChanges(DatabaseIcon $source, DatabaseIcon $destination): void
    {
        $destination->setFile($source->getFile());
    }

    /**
     * Returns the identifier of the specified icon.
     * @param DatabaseIcon $icon
     * @return string
     */
    protected function getIdentifier(DatabaseIcon $icon): string
    {
        return EntityUtils::buildIdentifier([$icon->getType(), $icon->getName()]);
    }
}
