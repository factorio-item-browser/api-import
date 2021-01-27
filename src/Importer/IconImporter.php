<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\Icon;
use FactorioItemBrowser\Api\Database\Repository\IconRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\DataCollector;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\Common\Constant\RecipeMode;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the icons.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractImporter<array{string, string, string}>
 */
class IconImporter extends AbstractImporter
{
    protected DataCollector $dataCollector;
    protected EntityManagerInterface $entityManager;
    protected IconRepository $repository;
    protected Validator $validator;

    public function __construct(
        DataCollector $dataCollector,
        EntityManagerInterface $entityManager,
        IconRepository $repository,
        Validator $validator
    ) {
        $this->dataCollector = $dataCollector;
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function prepare(Combination $combination): void
    {
        $this->repository->clearCombination($combination->getId());
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        foreach ($exportData->getMods() as $mod) {
            /* @var Mod $mod */
            if ($mod->thumbnailId !== '') {
                $this->dataCollector->addIconImageId($mod->thumbnailId);
                yield [EntityType::MOD, $mod->name, $mod->thumbnailId];
            }
        }

        foreach ($exportData->getItems() as $item) {
            /* @var Item $item */
            if ($item->iconId !== '') {
                $this->dataCollector->addIconImageId($item->iconId);
                yield [$item->type, $item->name, $item->iconId];
            }
        }

        foreach ($exportData->getMachines() as $machine) {
            /* @var Machine $machine */
            if ($machine->iconId !== '') {
                $this->dataCollector->addIconImageId($machine->iconId);
                yield [EntityType::MACHINE, $machine->name, $machine->iconId];
            }
        }

        foreach ($exportData->getRecipes() as $recipe) {
            /* @var Recipe $recipe */
            if ($recipe->mode === RecipeMode::NORMAL && $recipe->iconId !== '') {
                $this->dataCollector->addIconImageId($recipe->iconId);
                yield [EntityType::RECIPE, $recipe->name, $recipe->iconId];
            }
        }
    }

    /**
     * @param Combination $combination
     * @param ExportData $exportData
     * @param int $offset
     * @param int $limit
     * @throws ImportException
     */
    public function import(Combination $combination, ExportData $exportData, int $offset, int $limit): void
    {
        foreach ($this->getChunkedExportEntities($exportData, $offset, $limit) as $data) {
            $icon = $this->createIcon($data, $combination);

            $this->entityManager->persist($icon);
            $combination->getIcons()->add($icon);
        }
        $this->entityManager->flush();
    }

    /**
     * @param array{string, string, string}|string[] $data
     * @param Combination $combination
     * @return Icon
     * @throws ImportException
     */
    protected function createIcon(array $data, Combination $combination): Icon
    {
        [$type, $name, $iconId] = $data;
        $icon = new Icon();
        $icon->setType($type)
             ->setName($name)
             ->setImage($this->dataCollector->getIconImage($iconId))
             ->setCombination($combination);

        $this->validator->validateIcon($icon);
        return $icon;
    }

    public function cleanup(): void
    {
    }
}
