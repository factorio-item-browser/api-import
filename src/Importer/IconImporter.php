<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Icon;
use FactorioItemBrowser\Api\Database\Repository\IconRepository;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The importer of the icons.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class IconImporter implements ImporterInterface
{
    /**
     * The icon image importer.
     * @var IconImageImporter
     */
    protected $iconImageImporter;

    /**
     * The icon repository.
     * @var IconRepository
     */
    protected $iconRepository;

    /**
     * The parsed icons.
     * @var Icon[][]
     */
    protected $icons = [];

    /**
     * Initializes the importer.
     * @param IconImageImporter $iconImageImporter
     * @param IconRepository $iconRepository
     */
    public function __construct(IconImageImporter $iconImageImporter, IconRepository $iconRepository)
    {
        $this->iconImageImporter = $iconImageImporter;
        $this->iconRepository = $iconRepository;
    }

    /**
     * Prepares the data provided for the other importers.
     * @param ExportData $exportData
     */
    public function prepare(ExportData $exportData): void
    {
        $this->icons = [];
    }

    /**
     * Actually parses the data, having access to data provided by other importers.
     * @param ExportData $exportData
     */
    public function parse(ExportData $exportData): void
    {
        $this->processMods($exportData->getCombination()->getMods());
        $this->processItems($exportData->getCombination()->getItems());
        $this->processMachines($exportData->getCombination()->getMachines());
        $this->processRecipes($exportData->getCombination()->getRecipes());
    }

    /**
     * Processes the mods.
     * @param array|Mod[] $mods
     */
    protected function processMods(array $mods): void
    {
        foreach ($mods as $mod) {
            if ($mod->getThumbnailId() !== '') {
                $this->add($this->create(EntityType::MOD, $mod->getName(), $mod->getThumbnailId()));
            }
        }
    }

    /**
     * Processes the items.
     * @param array|Item[] $items
     */
    protected function processItems(array $items): void
    {
        foreach ($items as $item) {
            if ($item->getIconId() !== '') {
                $this->add($this->create($item->getType(), $item->getName(), $item->getIconId()));
            }
        }
    }

    /**
     * Processes the machines.
     * @param array|Machine[] $machines
     */
    protected function processMachines(array $machines): void
    {
        foreach ($machines as $machine) {
            if ($machine->getIconId() !== '') {
                $this->add($this->create(EntityType::MACHINE, $machine->getName(), $machine->getIconId()));
            }
        }
    }

    /**
     * Processes the recipes.
     * @param array|Recipe[] $recipes
     */
    protected function processRecipes(array $recipes): void
    {
        foreach ($recipes as $recipe) {
            if ($recipe->getIconId() !== '') {
                $this->add($this->create(EntityType::RECIPE, $recipe->getName(), $recipe->getIconId()));
            }
        }
    }

    /**
     * Creates a new icon.
     * @param string $type
     * @param string $name
     * @param string $imageId
     * @return Icon
     */
    protected function create(string $type, string $name, string $imageId): Icon
    {
        $icon = new Icon();
        $icon->setType($type)
             ->setName($name)
             ->setImage($this->iconImageImporter->getById($imageId));

        return $icon;
    }

    /**
     * Adds an icon to the local properties.
     * @param Icon $icon
     */
    protected function add(Icon $icon): void
    {
        $this->icons[$icon->getType()][$icon->getName()] = $icon;
    }

    /**
     * Persists the parsed data to the combination.
     * @param EntityManagerInterface $entityManager
     * @param Combination $combination
     */
    public function persist(EntityManagerInterface $entityManager, Combination $combination): void
    {
        $persistedIcons = [];
        foreach ($combination->getIcons() as $icon) {
            $key = $this->getKeyForIcon($icon);
            $persistedIcons[$key] = $icon;
        }

        foreach ($this->icons as $type => $iconsByType) {
            foreach ($iconsByType as $name => $icon) {
                $icon->setCombination($combination);

                $key = $this->getKeyForIcon($icon);
                if (isset($persistedIcons[$key])) {
                    $persistedIcons[$key]->setImage($icon->getImage());
                    unset($persistedIcons[$key]);
                } else {
                    $entityManager->persist($icon);
                }
            }
        }

        foreach ($persistedIcons as $icon) {
            $entityManager->remove($icon);
        }
    }

    /**
     * Returns  a key for the icon to detect duplicates.
     * @param Icon $icon
     * @return string
     */
    protected function getKeyForIcon(Icon $icon): string
    {
        return "{$icon->getCombination()->getId()->toString()}|{$icon->getType()}|{$icon->getName()}";
    }

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void
    {
    }
}
