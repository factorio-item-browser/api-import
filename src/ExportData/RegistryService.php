<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\ExportData;

use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Icon;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\Service\ExportDataService;

/**
 * The service of the export data registries.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class RegistryService
{
    /**
     * The export data service.
     * @var ExportDataService
     */
    protected $exportDataService;

    /**
     * Initializes the service.
     * @param ExportDataService $exportDataService
     */
    public function __construct(ExportDataService $exportDataService)
    {
        $this->exportDataService = $exportDataService;
    }

    /**
     * Returns the combination with the specified hash.
     * @param string $combinationHash
     * @return Combination
     * @throws UnknownHashException
     */
    public function getCombination(string $combinationHash): Combination
    {
        $result = $this->exportDataService->getCombinationRegistry()->get($combinationHash);
        if (!$result instanceof Combination) {
            throw new UnknownHashException('combination', $combinationHash);
        }
        return $result;
    }

    /**
     * Returns the icon with the specified hash.
     * @param string $iconHash
     * @return Icon
     * @throws UnknownHashException
     */
    public function getIcon(string $iconHash): Icon
    {
        $result = $this->exportDataService->getIconRegistry()->get($iconHash);
        if (!$result instanceof Icon) {
            throw new UnknownHashException('icon', $iconHash);
        }
        return $result;
    }

    /**
     * Returns the item with the specified hash.
     * @param string $itemHash
     * @return Item
     * @throws UnknownHashException
     */
    public function getItem(string $itemHash): Item
    {
        $result = $this->exportDataService->getItemRegistry()->get($itemHash);
        if (!$result instanceof Item) {
            throw new UnknownHashException(EntityType::ITEM, $itemHash);
        }
        return $result;
    }

    /**
     * Returns the machine with the specified hash.
     * @param string $machineHash
     * @return Machine
     * @throws UnknownHashException
     */
    public function getMachine(string $machineHash): Machine
    {
        $result = $this->exportDataService->getMachineRegistry()->get($machineHash);
        if (!$result instanceof Machine) {
            throw new UnknownHashException(EntityType::MACHINE, $machineHash);
        }
        return $result;
    }

    /**
     * Returns the mod with the specified name.
     * @param string $modName
     * @return Mod
     * @throws UnknownHashException
     */
    public function getMod(string $modName): Mod
    {
        $result = $this->exportDataService->getModRegistry()->get($modName);
        if (!$result instanceof Mod) {
            throw new UnknownHashException('mod', $modName);
        }
        return $result;
    }

    /**
     * Returns the recipe with the specified hash.
     * @param string $recipeHash
     * @return Recipe
     * @throws UnknownHashException
     */
    public function getRecipe(string $recipeHash): Recipe
    {
        $result = $this->exportDataService->getRecipeRegistry()->get($recipeHash);
        if (!$result instanceof Recipe) {
            throw new UnknownHashException(EntityType::RECIPE, $recipeHash);
        }
        return $result;
    }

    /**
     * Returns the rendered icon with the specified hash.
     * @param string $iconHash
     * @return string
     * @throws UnknownHashException
     */
    public function getRenderedIcon(string $iconHash): string
    {
        $result = $this->exportDataService->getRenderedIconRegistry()->get($iconHash);
        if ($result === null) {
            throw new UnknownHashException('rendered icon', $iconHash);
        }
        return $result;
    }
}
