<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Repository\ModCombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;

/**
 * The importer refreshing the order values of all combinations.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CombinationOrderImporter extends AbstractImporter implements GenericImporterInterface
{
    /**
     * The repository of the mod combinations.
     * @var ModCombinationRepository
     */
    protected $modCombinationRepository;

    /**
     * The repository of the mods.
     * @var ModRepository
     */
    protected $modRepository;

    /**
     * The orders of the mods.
     * @var array|int[]
     */
    protected $modOrders = [];

    /**
     * Initializes the importer.
     * @param EntityManager $entityManager
     * @param ModCombinationRepository $modCombinationRepository
     * @param ModRepository $modRepository
     */
    public function __construct(
        EntityManager $entityManager,
        ModCombinationRepository $modCombinationRepository,
        ModRepository $modRepository
    ) {
        parent::__construct($entityManager);
        $this->modCombinationRepository = $modCombinationRepository;
        $this->modRepository = $modRepository;
    }

    /**
     * Imports some generic data.
     * @throws ImportException
     */
    public function import(): void
    {
        $this->modOrders = $this->getModOrders();
        $combinations = $this->getOrderedCombinations();
        $this->assignOrder($combinations);
        $this->flushEntities();
    }

    /**
     * Returns the order values of all mods.
     * @return array|int[]
     */
    protected function getModOrders(): array
    {
        /* @var array|Mod[] $mods */
        $mods = $this->modRepository->findAll();

        $result = [];
        foreach ($mods as $mod) {
            $result[$mod->getId()] = $mod->getOrder();
        }
        return $result;
    }

    /**
     * Returns the ordered list of all mods.
     * @return array|ModCombination[]
     */
    protected function getOrderedCombinations(): array
    {
        /* @var array|ModCombination[] $result */
        $result = $this->modCombinationRepository->findAll();
        usort($result, [$this, 'compareCombinations']);
        return $result;
    }

    /**
     * Compares the two combinations.
     * @param ModCombination $left
     * @param ModCombination $right
     * @return int
     */
    protected function compareCombinations(ModCombination $left, ModCombination $right): int
    {
        $result = $this->modOrders[$left->getMod()->getId()] <=> $this->modOrders[$right->getMod()->getId()];
        if ($result === 0) {
            $leftOrders = $this->mapModIdsToOrders($left->getOptionalModIds());
            $rightOrders = $this->mapModIdsToOrders($right->getOptionalModIds());
            $result = count($leftOrders) <=> count($rightOrders);

            while ($result === 0 && count($leftOrders) > 0) {
                $result = array_shift($leftOrders) <=> array_shift($rightOrders);
            }
        }
        return $result;
    }

    /**
     * Maps the mod ids to their order values.
     * @param array|int[] $modIds
     * @return array|int[]
     */
    protected function mapModIdsToOrders(array $modIds): array
    {
        $result = [];
        foreach ($modIds as $modId) {
            $result[] = $this->modOrders[$modId];
        }
        sort($result);
        return $result;
    }

    /**
     * Assigns the order to the specified list of combinations.
     * @param array|ModCombination[] $orderedCombinations
     */
    protected function assignOrder(array $orderedCombinations): void
    {
        $order = 1;
        foreach ($orderedCombinations as $combination) {
            $combination->setOrder($order);
            ++$order;
        }
    }
}
