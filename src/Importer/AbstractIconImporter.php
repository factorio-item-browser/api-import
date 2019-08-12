<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconFile;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\IconFileRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;

/**
 * The abstract class of the icon importers.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class AbstractIconImporter extends AbstractImporter
{
    /**
     * The repository of the icon files.
     * @var IconFileRepository
     */
    protected $iconFileRepository;

    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * AbstractIconImporter constructor.
     * @param EntityManagerInterface $entityManager
     * @param IconFileRepository $iconFileRepository
     * @param RegistryService $registryService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        IconFileRepository $iconFileRepository,
        RegistryService $registryService
    ) {
        parent::__construct($entityManager);

        $this->iconFileRepository = $iconFileRepository;
        $this->registryService = $registryService;
    }

    /**
     * Fetches and updates the icon file with the specified hash.
     * @param string $iconHash
     * @return IconFile
     * @throws ImportException
     */
    protected function fetchIconFile(string $iconHash): IconFile
    {
        $iconFiles = $this->iconFileRepository->findByHashes([$iconHash]);
        $iconFile = array_shift($iconFiles);
        if (!$iconFile instanceof IconFile) {
            $iconFile = $this->createIconFile($iconHash);
        }

        $iconFile->setImage($this->registryService->getRenderedIcon($iconHash))
                 ->setSize($this->registryService->getIcon($iconHash)->getRenderedSize());
        return $iconFile;
    }

    /**
     * Creates a new icon file entity.
     * @param string $iconHash
     * @return IconFile
     * @throws ImportException
     */
    protected function createIconFile(string $iconHash): IconFile
    {
        $result = new IconFile($iconHash);
        $this->persistEntity($result);
        return $result;
    }

    /**
     * Creates the icon entity with the specified values.
     * @param DatabaseCombination $databaseCombination
     * @param IconFile $iconFile
     * @param string $type
     * @param string $name
     * @return DatabaseIcon
     */
    protected function createIcon(
        DatabaseCombination $databaseCombination,
        IconFile $iconFile,
        string $type,
        string $name
    ): DatabaseIcon {
        $result = new DatabaseIcon($databaseCombination, $iconFile);
        $result->setType($type)
               ->setName($name);
        return $result;
    }
}
