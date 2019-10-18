<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\ExportData;

/**
 * The importer of mods.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ModImporter implements ImporterInterface
{
    /**
     * The id calculator.
     * @var IdCalculator
     */
    protected $idCalculator;
    
    /**
     * The mod repository.
     * @var ModRepository
     */
    protected $modRepository;
    
    /**
     * The parsed mods.
     * @var array|DatabaseMod[]
     */
    protected $mods = [];

    /**
     * Initializes the importer.
     * @param IdCalculator $idCalculator
     * @param ModRepository $modRepository
     */
    public function __construct(IdCalculator $idCalculator, ModRepository $modRepository)
    {
        $this->idCalculator = $idCalculator;
        $this->modRepository = $modRepository;
    }

    /**
     * Prepares the data provided for the other importers.
     * @param ExportData $exportData
     */
    public function prepare(ExportData $exportData): void
    {
        $this->mods = [];
    }

    /**
     * Actually parses the data, having access to data provided by other importers.
     * @param ExportData $exportData
     */
    public function parse(ExportData $exportData): void
    {
        $ids = [];
        foreach ($exportData->getCombination()->getMods() as $exportMod) {
            $databaseMachine = $this->map($exportMod);
            $this->mods[$databaseMachine->getId()->toString()] = $databaseMachine;
            $ids[] = $databaseMachine->getId();
        }

        foreach ($this->modRepository->findByIds($ids) as $mod) {
            $this->mods[$mod->getId()->toString()] = $mod;
        }
    }

    /**
     * Maps the export mod to a database one.
     * @param ExportMod $exportMod
     * @return DatabaseMod
     */
    protected function map(ExportMod $exportMod): DatabaseMod
    {
        $databaseMod = new DatabaseMod();
        $databaseMod->setName($exportMod->getName())
                    ->setVersion($exportMod->getVersion())
                    ->setAuthor($exportMod->getAuthor());

        $databaseMod->setId($this->idCalculator->calculateIdOfMod($databaseMod));
        return $databaseMod;
    }

    /**
     * Persists the parsed data to the combination.
     * @param EntityManagerInterface $entityManager
     * @param Combination $combination
     */
    public function persist(EntityManagerInterface $entityManager, Combination $combination): void
    {
        $combination->getMods()->clear();
        foreach ($this->mods as $mod) {
            $entityManager->persist($mod);
            $combination->getMods()->add($mod);
        }
    }

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void
    {
        $this->modRepository->removeOrphans();
    }
}
