<?php

namespace FactorioItemBrowser\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconFile;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\IconFileRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The importer of the icons.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class IconImporter extends AbstractImporter implements CombinationImporterInterface
{
    /**
     * The repository of the icon files.
     * @var IconFileRepository
     */
    protected $iconFileRepository;

    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

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
     * Initializes the importer.
     * @param EntityManager $entityManager
     * @param IconFileRepository $iconFileRepository
     * @param RegistryService $registryService
     */
    public function __construct(
        EntityManager $entityManager,
        IconFileRepository $iconFileRepository,
        RegistryService $registryService
    ) {
        parent::__construct($entityManager);
        $this->iconFileRepository = $iconFileRepository;
        $this->registryService = $registryService;
    }

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
                $icon = $this->createIcon($iconFile, $item->getType(), $item->getName());
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
                $icon = $this->createIcon($iconFile, EntityType::RECIPE, $recipe->getName());
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
                $icon = $this->createIcon($iconFile, EntityType::MACHINE, $machine->getName());
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
     * Fetches and updates the icon file with the specified hash.
     * @param string $iconHash
     * @return IconFile
     * @throws ImportException
     */
    protected function fetchIconFile(string $iconHash): IconFile
    {
        $iconFiles = $this->iconFileRepository->findByHashes([$iconHash]);
        $iconFile = array_shift($iconFiles);
        if (!$iconFile instanceof IconFile) {
            $iconFile = new IconFile($iconHash);
            $this->persistEntity($iconFile);
        }

        $iconFile->setImage($this->registryService->getRenderedIcon($iconHash));
        return $iconFile;
    }

    /**
     * Creates the icon entity with the specified values.
     * @param IconFile $iconFile
     * @param string $type
     * @param string $name
     * @return DatabaseIcon
     */
    protected function createIcon(IconFile $iconFile, string $type, string $name): DatabaseIcon
    {
        $result = new DatabaseIcon($this->databaseCombination, $iconFile);
        $result->setType($type)
               ->setName($name);

        return $result;
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
