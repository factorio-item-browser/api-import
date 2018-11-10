<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\CachedSearchResult;
use FactorioItemBrowser\Api\Database\Repository\CachedSearchResultRepository;
use FactorioItemBrowser\Api\Import\Importer\Generic\ClearCacheImporter;
use FactorioItemBrowser\Api\Import\Importer\Generic\ClearCacheImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the ClearCacheImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\ClearCacheImporterFactory
 */
class ClearCacheImporterFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        /* @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
                              ->setMethods(['getRepository'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $entityManager->expects($this->once())
                      ->method('getRepository')
                      ->with(CachedSearchResult::class)
                      ->willReturn($this->createMock(CachedSearchResultRepository::class));

        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->once())
                  ->method('get')
                  ->with(EntityManager::class)
                  ->willReturn($entityManager);

        $factory = new ClearCacheImporterFactory();
        $factory($container, ClearCacheImporter::class);
    }
}
