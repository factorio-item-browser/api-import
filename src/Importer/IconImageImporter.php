<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\IconImageRepository;
use FactorioItemBrowser\ExportData\Entity\Icon;
use FactorioItemBrowser\ExportData\ExportData;
use Ramsey\Uuid\Uuid;

/**
 * The importer of the icon images.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class IconImageImporter implements ImporterInterface
{
    /**
     * The icon image repository.
     * @var IconImageRepository
     */
    protected $iconImageRepository;

    /**
     * The icons of the export.
     * @var array|Icon[]
     */
    protected $icons = [];

    /**
     * The parsed icon images.
     * @var array|IconImage[]
     */
    protected $images = [];

    /**
     * Initializes the importer.
     * @param IconImageRepository $iconImageRepository
     */
    public function __construct(IconImageRepository $iconImageRepository)
    {
        $this->iconImageRepository = $iconImageRepository;
    }

    /**
     * Prepares the data provided for the other importers.
     * @param ExportData $exportData
     */
    public function prepare(ExportData $exportData): void
    {
        $ids = [];
        foreach ($exportData->getCombination()->getIcons() as $icon) {
            $image = $this->create($icon);
            $ids[] = $image->getId();

            $this->icons[$image->getId()->toString()] = $icon;
            $this->images[$image->getId()->toString()] = $image;
        }

        foreach ($this->iconImageRepository->findByIds($ids) as $image) {
            $this->images[$image->getId()->toString()] = $image;
        }
    }

    /**
     * Creates the image entity from the icon.
     * @param Icon $icon
     * @return IconImage
     */
    public function create(Icon $icon): IconImage
    {
        $image = new IconImage();
        $image->setId(Uuid::fromString($icon->getHash()))
              ->setSize($icon->getRenderedSize());

        return $image;
    }

    /**
     * Returns the icon image with the specified id.
     * @param string $id
     * @return IconImage
     */
    public function getById(string $id): IconImage
    {
        return $this->images[$id];
    }

    /**
     * Actually parses the data, having access to data provided by other importers.
     * @param ExportData $exportData
     */
    public function parse(ExportData $exportData): void
    {
        // @todo Importing the image files must happen later as of memory issues...
//        foreach ($this->images as $image) {
//            $image->setContents($exportData->getRenderedIcon($this->icons[$image->getId()->toString()]));
//        }
    }

    /**
     * Persists the parsed data to the combination.
     * @param EntityManagerInterface $entityManager
     * @param Combination $combination
     */
    public function persist(EntityManagerInterface $entityManager, Combination $combination): void
    {
        foreach ($this->images as $image) {
            $entityManager->persist($image);
        }
    }

    /**
     * Cleans up any left-over data.
     */
    public function cleanup(): void
    {
        $this->iconImageRepository->removeOrphans();
    }
}
