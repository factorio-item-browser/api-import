<?php

namespace FactorioItemBrowser\Api\Import\Importer;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\IconFile;
use FactorioItemBrowser\Api\Database\Repository\IconFileRepository;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the icon importer.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class IconImporterFactory implements FactoryInterface
{
    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return IconImporter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $container->get(RegistryService::class);

        /* @var IconFileRepository $iconFileRepository */
        $iconFileRepository = $entityManager->getRepository(IconFile::class);

        return new IconImporter(
            $entityManager,
            $iconFileRepository,
            $registryService
        );
    }
}
