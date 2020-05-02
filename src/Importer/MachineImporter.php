<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Machine as DatabaseMachine;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The importer of the machines.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class MachineImporter implements ImporterInterface
{
    /**
     * The crafting category importer.
     * @var CraftingCategoryImporter
     */
    protected $craftingCategoryImporter;

    /**
     * The id calculator.
     * @var IdCalculator
     */
    protected $idCalculator;

    /**
     * The machine repository.
     * @var MachineRepository
     */
    protected $machineRepository;

    /**
     * The validator.
     * @var Validator
     */
    protected $validator;

    /**
     * The parsed machines.
     * @var array|DatabaseMachine[]
     */
    protected $machines = [];

    /**
     * Initializes the importer.
     * @param CraftingCategoryImporter $craftingCategoryImporter
     * @param IdCalculator $idCalculator
     * @param MachineRepository $machineRepository
     * @param Validator $validator
     */
    public function __construct(
        CraftingCategoryImporter $craftingCategoryImporter,
        IdCalculator $idCalculator,
        MachineRepository $machineRepository,
        Validator $validator
    ) {
        $this->craftingCategoryImporter = $craftingCategoryImporter;
        $this->idCalculator = $idCalculator;
        $this->machineRepository = $machineRepository;
        $this->validator = $validator;
    }

    /**
     * Prepares the data provided for the other importers.
     * @param ExportData $exportData
     */
    public function prepare(ExportData $exportData): void
    {
        $this->machines = [];
    }

    /**
     * Actually parses the data, having access to data provided by other importers.
     * @param ExportData $exportData
     * @throws ImportException
     */
    public function parse(ExportData $exportData): void
    {
        $ids = [];
        foreach ($exportData->getCombination()->getMachines() as $exportMachine) {
            $databaseMachine = $this->map($exportMachine);
            $ids[] = $databaseMachine->getId();

            $this->add($databaseMachine);
        }

        foreach ($this->machineRepository->findByIds($ids) as $machine) {
            $this->add($machine);
        }
    }

    /**
     * Maps the export machine to a database one.
     * @param ExportMachine $exportMachine
     * @return DatabaseMachine
     * @throws ImportException
     */
    protected function map(ExportMachine $exportMachine): DatabaseMachine
    {
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
            $databaseMachine->getCraftingCategories()->add(
                $this->craftingCategoryImporter->getByName($craftingCategory)
            );
        }

        $this->validator->validateMachine($databaseMachine);
        $databaseMachine->setId($this->idCalculator->calculateIdOfMachine($databaseMachine));
        return $databaseMachine;
    }

    /**
     * Adds the machine to the local properties.
     * @param DatabaseMachine $machine
     */
    protected function add(DatabaseMachine $machine): void
    {
        $this->machines[$machine->getId()->toString()] = $machine;
    }

    /**
     * Persists the parsed data to the combination.
     * @param EntityManagerInterface $entityManager
     * @param Combination $combination
     */
    public function persist(EntityManagerInterface $entityManager, Combination $combination): void
    {
        $combination->getMachines()->clear();
        foreach ($this->machines as $machine) {
            $entityManager->persist($machine);
            $combination->getMachines()->add($machine);
        }
    }

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void
    {
        $this->machineRepository->removeOrphans();

        // We may have created new orphans, so better be safe and cleanup again.
        $this->craftingCategoryImporter->cleanup();
    }
}
