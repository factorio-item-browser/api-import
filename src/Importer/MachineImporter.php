<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Data\MachineData;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory as DatabaseCraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Entity\Machine as DatabaseMachine;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Registry\EntityRegistry;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The importer of the machines.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class MachineImporter extends AbstractImporter
{
    /**
     * The repository of the crafting categories.
     * @var CraftingCategoryRepository
     */
    protected $craftingCategoryRepository;

    /**
     * The registry of the machines.
     * @var EntityRegistry
     */
    protected $machineRegistry;

    /**
     * The repository of the machines.
     * @var MachineRepository
     */
    protected $machineRepository;

    /**
     * The cached crafting categories.
     * @var array|CraftingCategory[]
     */
    protected $craftingCategoryCache = [];

    /**
     * Initializes the importer.
     * @param CraftingCategoryRepository $craftingCategoryRepository
     * @param EntityManager $entityManager
     * @param EntityRegistry $machineRegistry
     * @param MachineRepository $machineRepository
     */
    public function __construct(
        CraftingCategoryRepository $craftingCategoryRepository,
        EntityManager $entityManager,
        EntityRegistry $machineRegistry,
        MachineRepository $machineRepository
    ) {
        parent::__construct($entityManager);
        $this->craftingCategoryRepository = $craftingCategoryRepository;
        $this->machineRegistry = $machineRegistry;
        $this->machineRepository = $machineRepository;
    }

    /**
     * Imports the items.
     * @param ExportCombination $exportCombination
     * @param DatabaseCombination $databaseCombination
     * @throws ImportException
     */
    public function import(ExportCombination $exportCombination, DatabaseCombination $databaseCombination): void
    {
        $newMachines = $this->getMachinesFromExportCombination($exportCombination);
        $existingMachines = $this->getExistingMachines($newMachines);
        $persistedMachines = $this->persistEntities($newMachines, $existingMachines);
        $this->assignEntitiesToCollection($persistedMachines, $databaseCombination->getMachines());
    }

    /**
     * Returns the machines from the specified combination.
     * @param ExportCombination $exportCombination
     * @return array|DatabaseMachine[]
     * @throws ImportException
     * @throws UnknownHashException
     */
    protected function getMachinesFromExportCombination(ExportCombination $exportCombination): array
    {
        $result = [];
        foreach ($exportCombination->getMachineHashes() as $machineHash) {
            $exportMachine = $this->machineRegistry->get($machineHash);
            if (!$exportMachine instanceof ExportMachine) {
                throw new UnknownHashException(EntityType::MACHINE, $machineHash);
            }

            $databaseMachine = $this->mapMachine($exportMachine);
            $result[$this->getIdentifier($databaseMachine)] = $databaseMachine;
        }
        return $result;
    }

    /**
     * Maps the export machine to a database entity.
     * @param ExportMachine $machine
     * @return DatabaseMachine
     * @throws ImportException
     */
    protected function mapMachine(ExportMachine $machine): DatabaseMachine
    {
        $result = new DatabaseMachine($machine->getName());
        $result->setCraftingSpeed($machine->getCraftingSpeed())
               ->setNumberOfItemSlots($machine->getNumberOfItemSlots())
               ->setNumberOfFluidInputSlots($machine->getNumberOfFluidInputSlots())
               ->setNumberOfFluidOutputSlots($machine->getNumberOfFluidOutputSlots())
               ->setNumberOfModuleSlots($machine->getNumberOfModuleSlots())
               ->setEnergyUsage($machine->getEnergyUsage())
               ->setEnergyUsageUnit($machine->getEnergyUsageUnit());

        foreach ($machine->getCraftingCategories() as $name) {
            $result->getCraftingCategories()->add($this->getCraftingCategory($name));
        }
        return $result;
    }

    /**
     * Fetches the crafting category with the specified name from the database.
     * @param string $name
     * @return DatabaseCraftingCategory
     * @throws ImportException
     */
    protected function getCraftingCategory(string $name): DatabaseCraftingCategory
    {
        if (!isset($this->craftingCategoryCache[$name])) {
            $craftingCategories = $this->craftingCategoryRepository->findByNames([$name]);
            $craftingCategory = array_shift($craftingCategories);
            if (!$craftingCategory instanceof DatabaseCraftingCategory) {
                throw new ImportException('Missing crafting category: ' . $name);
            }
            $this->craftingCategoryCache[$name] = $craftingCategory;
        }
        return $this->craftingCategoryCache[$name];
    }

    /**
     * Returns the already existing entities of the specified machines.
     * @param array|DatabaseMachine[] $machines
     * @return array|DatabaseMachine[]
     */
    protected function getExistingMachines(array $machines): array
    {
        $machineNames = array_map(function (DatabaseMachine $machine): string {
            return $machine->getName();
        }, $machines);
        $machineData = $this->machineRepository->findDataByNames($machineNames);
        $machineIds = array_map(function (MachineData $machineData): int {
            return $machineData->getId();
        }, $machineData);

        $result = [];
        foreach ($this->machineRepository->findByIds($machineIds) as $machine) {
            $result[$this->getIdentifier($machine)] = $machine;
        }
        return $result;
    }

    /**
     * Returns the identifier of the machine.
     * @param DatabaseMachine $machine
     * @return string
     */
    protected function getIdentifier(DatabaseMachine $machine): string
    {
        $craftingCategories = array_map(function (CraftingCategory $craftingCategory): string {
            return $craftingCategory->getName();
        }, $machine->getCraftingCategories()->toArray());
        sort($craftingCategories);

        return EntityUtils::calculateHashOfArray([
            $machine->getName(),
            $machine->getCraftingSpeed(),
            array_values($craftingCategories),
            $machine->getNumberOfItemSlots(),
            $machine->getNumberOfFluidInputSlots(),
            $machine->getNumberOfFluidOutputSlots(),
            $machine->getNumberOfModuleSlots(),
            $machine->getEnergyUsage(),
            $machine->getEnergyUsageUnit(),
        ]);
    }
}
