<?php

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
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
     * @return array|DatabaseMod[]
     */
    protected function getOrderedMods(): array
    {
        /* @var DatabaseMod[] $databaseMods */
        $databaseMods = $this->modRepository->findAll();

        usort($databaseMods, function(DatabaseMod $left, DatabaseMod $right): int {
            $leftOrder = $this->registryService->getMod($left->getName())->getOrder();
            $rightOrder = $this->registryService->getMod($right->getName())->getOrder();
            return $leftOrder <=> $rightOrder;
        });

        return $databaseMods;
    }

    /**
     * Assigns the order to the specified list of mods.
     * @param array|DatabaseMod[] $orderedMods
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
