<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Command;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\IconImageRepository;
use FactorioItemBrowser\Api\Import\Constant\ParameterName;
use FactorioItemBrowser\ExportData\ExportDataService;
use Ramsey\Uuid\Uuid;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * The command for importing all the image data of a combination.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ImportImagesCommand implements CommandInterface
{
    /**
     * The entity manager.
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * The export data service.
     * @var ExportDataService
     */
    protected $exportDataService;

    /**
     * The icon image repository.
     * @var IconImageRepository
     */
    protected $iconImageRepository;

    /**
     * Initializes the command.
     * @param EntityManagerInterface $entityManager
     * @param ExportDataService $exportDataService
     * @param IconImageRepository $iconImageRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ExportDataService $exportDataService,
        IconImageRepository $iconImageRepository
    ) {
        $this->entityManager = $entityManager;
        $this->exportDataService = $exportDataService;
        $this->iconImageRepository = $iconImageRepository;
    }

    /**
     * Invokes the command.
     * @param Route $route
     * @param AdapterInterface $consoleAdapter
     * @return int
     */
    public function __invoke(Route $route, AdapterInterface $consoleAdapter): int
    {
        $combinationId = $route->getMatchedParam(ParameterName::COMBINATION, '');

        $exportData = $this->exportDataService->loadExport($combinationId);
        foreach ($exportData->getCombination()->getIcons() as $icon) {
            $image = $this->iconImageRepository->findByIds([Uuid::fromString($icon->getHash())])[0];
            if (!$image instanceof IconImage) {
                continue;
            }

            $image->setContents($exportData->getRenderedIcon($icon));

            $this->entityManager->persist($image);
            $this->entityManager->flush();
            $this->entityManager->detach($image);
        }

        return 0;
    }
}
