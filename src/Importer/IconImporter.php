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
use FactorioItemBrowser\ExportData\ExportData;
use Generator;

/**
 * The importer for the icons.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractImporter<Icon>
 */
class IconImporter extends AbstractImporter
{
    protected DataCollector $dataCollector;
    protected EntityManagerInterface $entityManager;
    protected IconRepository $iconRepository;
    protected Validator $validator;

    public function __construct(
        DataCollector $dataCollector,
        EntityManagerInterface $entityManager,
        IconRepository $iconRepository,
        Validator $validator
    ) {
        $this->dataCollector = $dataCollector;
        $this->entityManager = $entityManager;
        $this->iconRepository = $iconRepository;
        $this->validator = $validator;
    }

    public function prepare(Combination $combination): void
    {
        $this->iconRepository->clearCombination($combination->getId());
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        foreach ($exportData->getCombination()->getMods() as $mod) {
            if ($mod->getThumbnailId() !== '') {
                $this->dataCollector->addIconImageId($mod->getThumbnailId());
                yield [EntityType::MOD, $mod->getName(), $mod->getThumbnailId()];
            }
        }

        foreach ($exportData->getCombination()->getItems() as $item) {
            if ($item->getIconId() !== '') {
                $this->dataCollector->addIconImageId($item->getIconId());
                yield [$item->getType(), $item->getName(), $item->getIconId()];
            }
        }

        foreach ($exportData->getCombination()->getMachines() as $machine) {
            if ($machine->getIconId() !== '') {
                $this->dataCollector->addIconImageId($machine->getIconId());
                yield [EntityType::MACHINE, $machine->getName(), $machine->getIconId()];
            }
        }

        foreach ($exportData->getCombination()->getRecipes() as $recipe) {
            if ($recipe->getMode() === RecipeMode::NORMAL && $recipe->getIconId() !== '') {
                $this->dataCollector->addIconImageId($recipe->getIconId());
                yield [EntityType::RECIPE, $recipe->getName(), $recipe->getIconId()];
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
     * @param array<{string, string, string}|string[] $data
     * @param Combination $combination
     * @return Icon
     * @throws ImportException
     */
    protected function createIcon($data, Combination $combination)
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
