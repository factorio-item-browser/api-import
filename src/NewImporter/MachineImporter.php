<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\NewImporter;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Machine as DatabaseMachine;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\MissingCraftingCategoryException;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 *
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractImporter<ExportMachine, DatabaseMachine>
 */
class MachineImporter extends AbstractImporter
{
    protected CraftingCategoryRepository $craftingCategoryRepository;
    protected IdCalculator $idCalculator;

    /**
     * @var array<string,CraftingCategory|null>
     */
    protected array $craftingCategories = [];

    protected bool $fetchedCraftingCategories = false;

    public function __construct(
        CraftingCategoryRepository $craftingCategoryRepository,
        EntityManagerInterface $entityManager,
        IdCalculator $idCalculator,
        MachineRepository $repository
    ) {
        parent::__construct($entityManager, $repository);

        $this->craftingCategoryRepository = $craftingCategoryRepository;
        $this->idCalculator = $idCalculator;
    }

    protected function getCollectionFromCombination(Combination $combination): Collection
    {
        return $combination->getMachines();
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        foreach ($exportData->getCombination()->getMachines() as $machine) {
            foreach ($machine->getCraftingCategories() as $craftingCategory) {
                $this->craftingCategories[$craftingCategory] = null;
            }

            yield $machine;
        }
    }

    /**
     * @param ExportMachine $exportMachine
     * @return DatabaseMachine
     * @throws ImportException
     */
    protected function createDatabaseEntity($exportMachine): DatabaseMachine
    {
        $this->fetchCraftingCategories();

        $databaseMachine = new DatabaseMachine();
        $databaseMachine->setName($exportMachine->getName())
                        ->setCraftingSpeed($exportMachine->getCraftingSpeed())
                        ->setNumberOfItemSlots($exportMachine->getNumberOfItemSlots())
                        ->setNumberOfFluidInputSlots($exportMachine->getNumberOfFluidInputSlots())
                        ->setNumberOfFluidOutputSlots($exportMachine->getNumberOfFluidOutputSlots())
                        ->setNumberOfModuleSlots($exportMachine->getNumberOfModuleSlots())
                        ->setEnergyUsage($exportMachine->getEnergyUsage())
                        ->setEnergyUsageUnit($exportMachine->getEnergyUsageUnit());

        foreach ($exportMachine->getCraftingCategories() as $craftingCategory) {
            $databaseMachine->getCraftingCategories()->add($this->getCraftingCategory($craftingCategory));
        }

        $databaseMachine->setId($this->idCalculator->calculateIdOfMachine($databaseMachine));
        return $databaseMachine;
    }

    protected function fetchCraftingCategories(): void
    {
        if (!$this->fetchedCraftingCategories) {
            $craftingCategories = $this->craftingCategoryRepository->findByNames(array_keys($this->craftingCategories));
            foreach ($craftingCategories as $craftingCategory) {
                $this->craftingCategories[$craftingCategory->getName()] = $craftingCategory;
            }

            $this->fetchedCraftingCategories = true;
        }
    }

    /**
     * @param string $name
     * @return CraftingCategory
     * @throws ImportException
     */
    protected function getCraftingCategory(string $name): CraftingCategory
    {
        if (!isset($this->craftingCategories[$name])) {
            throw new MissingCraftingCategoryException($name);
        }
        return $this->craftingCategories[$name];
    }
}
