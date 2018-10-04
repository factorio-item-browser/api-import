<?php

namespace FactorioItemBrowser\Api\Import\ExportData;

use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\Service\ExportDataService;

/**
 * Th4e service of the export data registries.
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
}
