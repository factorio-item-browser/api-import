<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\CraftingCategoryImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\CraftingCategoryImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the CraftingCategoryImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Combination\CraftingCategoryImporterFactory
 */
class CraftingCategoryImporterFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);

        /* @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
                              ->setMethods(['getRepository'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $entityManager->expects($this->once())
                      ->method('getRepository')
                      ->with(CraftingCategory::class)
                      ->willReturn($craftingCategoryRepository);

        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->exactly(2))
                  ->method('get')
                  ->withConsecutive(
                      [EntityManager::class],
                      [RegistryService::class]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $entityManager,
                      $this->createMock(RegistryService::class)
                  );

        $factory = new CraftingCategoryImporterFactory();
        $factory($container, CraftingCategoryImporter::class);
    }
}
