<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Helper;

use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Entity\Machine;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Entity\Recipe;
use FactorioItemBrowser\Api\Database\Entity\RecipeIngredient;
use FactorioItemBrowser\Api\Database\Entity\RecipeProduct;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * The calculator of ids.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class IdCalculator
{
    /**
     * Calculates the id for the specified crafting category.
     * @param CraftingCategory $craftingCategory
     * @return UuidInterface
     */
    public function calculateIdOfCraftingCategory(CraftingCategory $craftingCategory): UuidInterface
    {
        return $this->calculateId([
            $craftingCategory->getName(),
        ]);
    }

    /**
     * Calculates the id for the specified item.
     * @param Item $item
     * @return UuidInterface
     */
    public function calculateIdOfItem(Item $item): UuidInterface
    {
        return $this->calculateId([
            $item->getType(),
            $item->getName(),
        ]);
    }

    /**
     * Calculates the id for the specified machine.
     * @param Machine $machine
     * @return UuidInterface
     */
    public function calculateIdOfMachine(Machine $machine): UuidInterface
    {
        return $this->calculateId([
            $machine->getName(),
            array_map(function (CraftingCategory $craftingCategory): string {
                return $craftingCategory->getId()->toString();
            }, $machine->getCraftingCategories()->toArray()),
            $machine->getCraftingSpeed(),
            $machine->getNumberOfItemSlots(),
            $machine->getNumberOfFluidInputSlots(),
            $machine->getNumberOfFluidOutputSlots(),
            $machine->getNumberOfModuleSlots(),
            $machine->getEnergyUsage(),
            $machine->getEnergyUsageUnit(),
        ]);
    }

    /**
     * Calculates the id for the specified mod.
     * @param Mod $mod
     * @return UuidInterface
     */
    public function calculateIdOfMod(Mod $mod): UuidInterface
    {
        return $this->calculateId([
            $mod->getName(),
            $mod->getVersion(),
        ]);
    }

    /**
     * Calculates the id for the specified recipe.
     * @param Recipe $recipe
     * @return UuidInterface
     */
    public function calculateIdOfRecipe(Recipe $recipe): UuidInterface
    {
        return $this->calculateId([
            $recipe->getName(),
            $recipe->getMode(),
            array_map(function (RecipeIngredient $ingredient): array {
                return [
                    $ingredient->getItem()->getType(),
                    $ingredient->getItem()->getName(),
                    $ingredient->getAmount(),
                ];
            }, $recipe->getIngredients()->toArray()),
            array_map(function (RecipeProduct $product): array {
                return [
                    $product->getItem()->getType(),
                    $product->getItem()->getName(),
                    $product->getAmountMin(),
                    $product->getAmountMax(),
                    $product->getProbability(),
                ];
            }, $recipe->getProducts()->toArray()),
        ]);
    }

    /**
     * Calculates the id for the specified translation.
     * @param Translation $translation
     * @return UuidInterface
     */
    public function calculateIdOfTranslation(Translation $translation): UuidInterface
    {
        return $this->calculateId([
            $translation->getLocale(),
            $translation->getType(),
            $translation->getName(),
            $translation->getValue(),
            $translation->getDescription(),
            $translation->getIsDuplicatedByMachine(),
            $translation->getIsDuplicatedByRecipe(),
        ]);
    }

    /**
     * Calculates the id for the specified data.
     * @param array<mixed> $data
     * @return UuidInterface
     */
    protected function calculateId(array $data): UuidInterface
    {
        return Uuid::fromString(hash('md5', (string) json_encode($data)));
    }
}
