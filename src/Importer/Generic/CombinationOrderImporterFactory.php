<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Repository\ModCombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the combination order importer.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CombinationOrderImporterFactory implements FactoryInterface
{
    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return CombinationOrderImporter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $container->get(ModCombinationRepository::class);
        /* @var ModRepository $modRepository */
        $modRepository = $container->get(ModRepository::class);

        return new CombinationOrderImporter(
            $entityManager,
            $modCombinationRepository,
            $modRepository
        );
    }
}
