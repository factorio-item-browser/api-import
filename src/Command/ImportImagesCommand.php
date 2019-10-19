<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\IconImageRepository;
use FactorioItemBrowser\ExportData\Entity\Icon;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use Ramsey\Uuid\Uuid;

/**
 * The command for importing all the image data of a combination.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ImportImagesCommand extends AbstractCombinationImportCommand
{
    /**
     * The entity manager.
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * The icon image repository.
     * @var IconImageRepository
     */
    protected $iconImageRepository;

    /**
     * Initializes the command.
     * @param CombinationRepository $combinationRepository
     * @param EntityManagerInterface $entityManager
     * @param ExportDataService $exportDataService
     * @param IconImageRepository $iconImageRepository
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        EntityManagerInterface $entityManager,
        ExportDataService $exportDataService,
        IconImageRepository $iconImageRepository
    ) {
        parent::__construct($combinationRepository, $exportDataService);
        $this->entityManager = $entityManager;
        $this->iconImageRepository = $iconImageRepository;
    }

    /**
     * Imports the export data into the combination.
     * @param ExportData $exportData
     * @param Combination $combination
     */
    protected function import(ExportData $exportData, Combination $combination): void
    {
        foreach ($exportData->getCombination()->getIcons() as $icon) {
            $image = $this->getImage($icon);
            if ($image !== null) {
                $image->setContents($exportData->getRenderedIcon($icon));
                $this->persist($image);
            }
        }
    }

    /**
     * Returns the image entity to the icon.
     * @param Icon $icon
     * @return IconImage|null
     */
    protected function getImage(Icon $icon): ?IconImage
    {
        $images = $this->iconImageRepository->findByIds([Uuid::fromString($icon->getId())]);
        return array_shift($images);
    }

    /**
     * Persists the image into the database.
     * @param IconImage $image
     */
    protected function persist(IconImage $image): void
    {
        $this->entityManager->persist($image);
        $this->entityManager->flush();
        $this->entityManager->detach($image);
    }
}
