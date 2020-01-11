<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\IconImageRepository;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
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
class ImportImagesCommand extends AbstractImportCommand
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
     * @param Console $console
     * @param EntityManagerInterface $entityManager
     * @param ExportDataService $exportDataService
     * @param IconImageRepository $iconImageRepository
     */
    public function __construct(
        CombinationRepository $combinationRepository,
        Console $console,
        EntityManagerInterface $entityManager,
        ExportDataService $exportDataService,
        IconImageRepository $iconImageRepository
    ) {
        parent::__construct($combinationRepository, $console, $exportDataService);
        $this->entityManager = $entityManager;
        $this->iconImageRepository = $iconImageRepository;
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName(CommandName::IMPORT_IMAGES);
        $this->setDescription('Imports the images of a combination.');
    }

    /**
     * Returns a label describing what the import is doing.
     * @return string
     */
    protected function getLabel(): string
    {
        return 'Processing the images of the icons';
    }

    /**
     * Imports the export data into the combination.
     * @param ExportData $exportData
     * @param Combination $combination
     */
    protected function import(ExportData $exportData, Combination $combination): void
    {
        foreach ($exportData->getCombination()->getIcons() as $icon) {
            $this->console->writeAction(sprintf('Importing image of icon %s', $icon->getId()));
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
