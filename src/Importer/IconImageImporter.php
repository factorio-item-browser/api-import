<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\IconImageRepository;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\ExportData\Entity\Icon as ExportIcon;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;
use Ramsey\Uuid\Uuid;

/**
 * The importer of the icon images.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 *
 * @extends AbstractImporter<ExportIcon>
 */
class IconImageImporter extends AbstractImporter
{
    protected EntityManagerInterface $entityManager;
    protected IconImageRepository $repository;
    protected Validator $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        IconImageRepository $repository,
        Validator $validator
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->validator = $validator;
    }

    protected function getExportEntities(ExportData $exportData): Generator
    {
        yield from $exportData->getIcons();
    }

    public function prepare(Combination $combination): void
    {
    }

    public function import(Combination $combination, ExportData $exportData, int $offset, int $limit): void
    {
        $iconImages = [];
        foreach ($this->getChunkedExportEntities($exportData, $offset, $limit) as $exportIcon) {
            $iconImages[] = $this->createIconImage($exportIcon, $exportData);
        }
        $this->persistIconImages($iconImages);
    }

    protected function createIconImage(ExportIcon $exportIcon, ExportData $exportData): IconImage
    {
        $iconImage = new IconImage();
        $iconImage->setId(Uuid::fromString($exportIcon->id))
                  ->setSize($exportIcon->size)
                  ->setContents($exportData->getRenderedIcons()->get($exportIcon->id));

        $this->validator->validateIconImage($iconImage);
        return $iconImage;
    }

    /**
     * @param array<IconImage> $entities
     */
    protected function persistIconImages(array $entities): void
    {
        $ids = [];
        $mappedEntities = [];
        foreach ($entities as $entity) {
            $id = $entity->getId();
            $ids[] = $id;
            $mappedEntities[$id->toString()] = $entity;
        }

        $existingEntities = $this->repository->findByIds($ids);
        foreach ($existingEntities as $existingEntity) {
            $id = $existingEntity->getId()->toString();
            $this->updateIconImage($mappedEntities[$id], $existingEntity);
            $mappedEntities[$id] = $existingEntity;
        }

        foreach ($mappedEntities as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
    }

    protected function updateIconImage(IconImage $source, IconImage $destination): void
    {
        $destination->setSize($source->getSize())
                    ->setContents($source->getContents());
    }

    public function cleanup(): void
    {
        $this->repository->removeOrphans();
    }
}
