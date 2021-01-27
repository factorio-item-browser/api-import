<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Machine as DatabaseMachine;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\DataCollector;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the machines.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractEntityImporter<ExportMachine, DatabaseMachine>
 */
class MachineImporter extends AbstractEntityImporter
{
    protected DataCollector $dataCollector;
    protected IdCalculator $idCalculator;
    protected Validator $validator;

    public function __construct(
        DataCollector $dataCollector,
        EntityManagerInterface $entityManager,
        IdCalculator $idCalculator,
        MachineRepository $repository,
        Validator $validator
    ) {
        parent::__construct($entityManager, $repository);

        $this->dataCollector = $dataCollector;
        $this->idCalculator = $idCalculator;
        $this->validator = $validator;
    }

    protected function getCollectionFromCombination(Combination $combination): Collection
    {
        return $combination->getMachines();
    }

    protected function prepareImport(Combination $combination, ExportData $exportData, int $offset, int $limit): void
    {
        $this->dataCollector->setCombination($combination);
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        foreach ($exportData->getMachines() as $machine) {
            /* @var Machine $machine */
            foreach ($machine->craftingCategories as $craftingCategory) {
                $this->dataCollector->addCraftingCategoryName($craftingCategory);
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
        $databaseMachine = new DatabaseMachine();
        $databaseMachine->setName($exportMachine->name)
                        ->setCraftingSpeed($exportMachine->craftingSpeed)
                        ->setNumberOfItemSlots($exportMachine->numberOfItemSlots)
                        ->setNumberOfFluidInputSlots($exportMachine->numberOfFluidInputSlots)
                        ->setNumberOfFluidOutputSlots($exportMachine->numberOfFluidOutputSlots)
                        ->setNumberOfModuleSlots($exportMachine->numberOfModuleSlots)
                        ->setEnergyUsage($exportMachine->energyUsage)
                        ->setEnergyUsageUnit($exportMachine->energyUsageUnit);

        foreach ($exportMachine->craftingCategories as $craftingCategory) {
            $databaseMachine->getCraftingCategories()->add(
                $this->dataCollector->getCraftingCategory($craftingCategory),
            );
        }

        $this->validator->validateMachine($databaseMachine);
        $databaseMachine->setId($this->idCalculator->calculateIdOfMachine($databaseMachine));
        return $databaseMachine;
    }
}
