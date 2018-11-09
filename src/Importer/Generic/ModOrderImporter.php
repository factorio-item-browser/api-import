<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;

/**
 * The importer refreshing the order values of all mods.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ModOrderImporter extends AbstractImporter implements GenericImporterInterface
{
    /**
     * The repository of the mods.
     * @var ModRepository
     */
    protected $modRepository;

    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * ModOrderImporter constructor.
     * @param EntityManager $entityManager
     * @param ModRepository $modRepository
     * @param RegistryService $registryService
     */
    public function __construct(
        EntityManager $entityManager,
        ModRepository $modRepository,
        RegistryService $registryService
    ) {
        parent::__construct($entityManager);
        $this->modRepository = $modRepository;
        $this->registryService = $registryService;
    }

    /**
     * Imports some generic data.
     * @throws ImportException
     */
    public function import(): void
    {
        $orderedMods = $this->getOrderedMods();
        $this->assignOrder($orderedMods);
        $this->flushEntities();
    }

    /**
     * Returns the ordered list of all mods.
     * @return array|Mod[]
     */
    protected function getOrderedMods(): array
    {
        /* @var Mod[] $databaseMods */
        $databaseMods = $this->modRepository->findAll();
        usort($databaseMods, [$this, 'compareMods']);
        return $databaseMods;
    }

    /**
     * Compares the two mods.
     * @param Mod $left
     * @param Mod $right
     * @return int
     * @throws ImportException
     */
    protected function compareMods(Mod $left, Mod $right): int
    {
        return $this->getOrderByModName($left->getName()) <=> $this->getOrderByModName($right->getName());
    }

    /**
     * Returns the order of the mod with the specified name.
     * @param string $modName
     * @return int
     * @throws ImportException
     */
    protected function getOrderByModName(string $modName): int
    {
        return $this->registryService->getMod($modName)->getOrder();
    }

    /**
     * Assigns the order to the specified list of mods.
     * @param array|Mod[] $orderedMods
     */
    protected function assignOrder(array $orderedMods): void
    {
        $order = 1;
        foreach ($orderedMods as $mod) {
            $mod->setOrder($order);
            ++$order;
        }
    }
}
