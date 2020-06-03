<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Helper;

use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Icon;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Entity\Machine;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Entity\Recipe;
use FactorioItemBrowser\Api\Database\Entity\RecipeIngredient;
use FactorioItemBrowser\Api\Database\Entity\RecipeProduct;

/**
 * The validator for the database entities.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class Validator
{
    /**
     * Validates the crafting category.
     * @param CraftingCategory $craftingCategory
     */
    public function validateCraftingCategory(CraftingCategory $craftingCategory): void
    {
        $craftingCategory->setName($this->limitString($craftingCategory->getName(), 255));
    }

    /**
     * Validates the icon.
     * @param Icon $icon
     */
    public function validateIcon(Icon $icon): void
    {
        $icon->setName($this->limitString($icon->getName(), 255));
    }

    /**
     * Validates the icon image.
     * @param IconImage $iconImage
     */
    public function validateIconImage(IconImage $iconImage): void
    {
        $iconImage->setSize($this->limitInteger($iconImage->getSize(), 0, 65535));
    }

    /**
     * Validates the item.
     * @param Item $item
     */
    public function validateItem(Item $item): void
    {
        $item->setName($this->limitString($item->getName(), 255));
    }

    /**
     * Validates the machine.
     * @param Machine $machine
     */
    public function validateMachine(Machine $machine): void
    {
        $machine->setName($this->limitString($machine->getName(), 255))
                ->setCraftingSpeed($this->limitFloat($machine->getCraftingSpeed()))
                ->setNumberOfItemSlots($this->limitInteger($machine->getNumberOfItemSlots(), 0, 255))
                ->setNumberOfFluidInputSlots($this->limitInteger($machine->getNumberOfFluidInputSlots(), 0, 255))
                ->setNumberOfFluidOutputSlots($this->limitInteger($machine->getNumberOfFluidOutputSlots(), 0, 255))
                ->setNumberOfModuleSlots($this->limitInteger($machine->getNumberOfModuleSlots(), 0, 255))
                ->setEnergyUsage($this->limitFloat($machine->getEnergyUsage()));
    }

    /**
     * Validates the mod.
     * @param Mod $mod
     */
    public function validateMod(Mod $mod): void
    {
        $mod->setAuthor($this->limitString($mod->getAuthor(), 255))
            ->setVersion($this->limitString($mod->getVersion(), 16));
    }

    /**
     * Validates the recipe.
     * @param Recipe $recipe
     */
    public function validateRecipe(Recipe $recipe): void
    {
        $recipe->setName($this->limitString($recipe->getName(), 255))
               ->setCraftingTime($this->limitFloat($recipe->getCraftingTime()));

        foreach ($recipe->getIngredients() as $ingredient) {
            $this->validateIngredient($ingredient);
        }
        foreach ($recipe->getProducts() as $product) {
            $this->validateProduct($product);
        }
    }

    /**
     * Validates the ingredient.
     * @param RecipeIngredient $ingredient
     */
    protected function validateIngredient(RecipeIngredient $ingredient): void
    {
        $ingredient->setOrder($this->limitInteger($ingredient->getOrder(), 0, 255))
                   ->setAmount($this->limitFloat($ingredient->getAmount()));
    }

    /**
     * Validates the product.
     * @param RecipeProduct $product
     */
    protected function validateProduct(RecipeProduct $product): void
    {
        $product->setOrder($this->limitInteger($product->getOrder(), 0, 255))
                ->setAmountMin($this->limitFloat($product->getAmountMin()))
                ->setAmountMax($this->limitFloat($product->getAmountMax()))
                ->setProbability($this->limitFloat($product->getProbability()));
    }

    /**
     * Limits the string to the specified length.
     * @param string $string
     * @param int $maxLength
     * @return string
     */
    protected function limitString(string $string, int $maxLength): string
    {
        return substr(trim($string), 0, $maxLength);
    }

    /**
     * Limits the integer to the specified boundaries.
     * @param int $integer
     * @param int $minValue
     * @param int $maxValue
     * @return int
     */
    protected function limitInteger(int $integer, int $minValue, int $maxValue): int
    {
        return min(max($integer, $minValue), $maxValue);
    }

    /**
     * Limits the float, represented as 32bit integer in the database.
     * @param float $float
     * @return float
     */
    protected function limitFloat(float $float): float
    {
        return min(max($float * 1000, 0), 4294967295) / 1000;
    }
}
