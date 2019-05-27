<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Machine as DatabaseMachine;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Import\Database\CraftingCategoryService;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Utils\EntityUtils;

/**
 * The importer of the machines.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class MachineImporter extends AbstractImporter implements CombinationImporterInterface
{
    /**
     * The service of the crafting categories.
     * @var CraftingCategoryService
     */
    protected $craftingCategoryService;

    /**
     * The repository of the machines.
     * @var MachineRepository
     */
    protected $machineRepository;
    
    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * Initializes the importer.
     * @param CraftingCategoryService $craftingCategoryService
     * @param EntityManagerInterface $entityManager
     * @param MachineRepository $machineRepository
     * @param RegistryService $registryService
     */
    public function __construct(
        CraftingCategoryService $craftingCategoryService,
        EntityManagerInterface $entityManager,
        MachineRepository $machineRepository,
        RegistryService $registryService
    ) {
        parent::__construct($entityManager);

        $this->craftingCategoryService = $craftingCategoryService;
        $this->machineRepository = $machineRepository;
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
        $newMachines = $this->getMachinesFromCombination($exportCombination);
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
    protected function getMachinesFromCombination(ExportCombination $exportCombination): array
    {
        $result = [];
        foreach ($exportCombination->getMachineHashes() as $machineHash) {
            $exportMachine = $this->registryService->getMachine($machineHash);
            if ($this->hasMachineData($exportMachine)) {
                $databaseMachine = $this->mapMachine($exportMachine);
                $result[$this->getIdentifier($databaseMachine)] = $databaseMachine;
            }
        }
        return $result;
    }

    /**
     * Returns whether the machine has actual data to be imported.
     * @param ExportMachine $machine
     * @return bool
     */
    protected function hasMachineData(ExportMachine $machine): bool
    {
        return count($machine->getCraftingCategories()) > 0;
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
            $result->getCraftingCategories()->add($this->craftingCategoryService->getByName($name));
        }
        return $result;
    }

    /**
     * Returns the already existing entities of the specified machines.
     * @param array|DatabaseMachine[] $machines
     * @return array|DatabaseMachine[]
     */
    protected function getExistingMachines(array $machines): array
    {
        $machineNames = [];
        foreach ($machines as $machine) {
            $machineNames[] = $machine->getName();
        }

        $machineIds = [];
        foreach ($this->machineRepository->findDataByNames($machineNames) as $machineData) {
            $machineIds[] = $machineData->getId();
        }

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
        $craftingCategories = [];
        foreach ($machine->getCraftingCategories() as $craftingCategory) {
            $craftingCategories[] = $craftingCategory->getName();
        }
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
