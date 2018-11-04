<?php

namespace FactorioItemBrowser\Api\Import\Importer\Mod;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the translation importer.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class TranslationImporterFactory implements FactoryInterface
{
    /**
     * Creates the importer.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return TranslationImporter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        return new TranslationImporter(
            $entityManager
        );
    }
}
