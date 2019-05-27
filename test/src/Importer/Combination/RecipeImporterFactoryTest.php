<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Import\Database\CraftingCategoryService;
use FactorioItemBrowser\Api\Import\Database\ItemService;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\RecipeImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\RecipeImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the RecipeImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Combination\RecipeImporterFactory
 */
class RecipeImporterFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->exactly(5))
                  ->method('get')
                  ->withConsecutive(
                      [CraftingCategoryService::class],
                      [EntityManagerInterface::class],
                      [ItemService::class],
                      [RecipeRepository::class],
                      [RegistryService::class]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $this->createMock(CraftingCategoryService::class),
                      $this->createMock(EntityManagerInterface::class),
                      $this->createMock(ItemService::class),
                      $this->createMock(RecipeRepository::class),
                      $this->createMock(RegistryService::class)
                  );

        $factory = new RecipeImporterFactory();
        $factory($container, RecipeImporter::class);
    }
}
