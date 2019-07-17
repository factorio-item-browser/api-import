<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Database\Repository\IconFileRepository;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Database\Repository\MachineRepository;
use FactorioItemBrowser\Api\Database\Repository\RecipeRepository;
use FactorioItemBrowser\Api\Database\Repository\RepositoryWithOrphansInterface;
use FactorioItemBrowser\Api\Import\Importer\Generic\CleanupImporter;
use FactorioItemBrowser\Api\Import\Importer\Generic\CleanupImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the CleanupImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\CleanupImporterFactory
 */
class CleanupImporterFactoryTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the invoking.
     * @throws ReflectionException
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        $repositories = [
            $this->createMock(RepositoryWithOrphansInterface::class),
            $this->createMock(RepositoryWithOrphansInterface::class),
        ];

        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->once())
                  ->method('get')
                  ->with(EntityManagerInterface::class)
                  ->willReturn($this->createMock(EntityManagerInterface::class));

        /* @var CleanupImporterFactory|MockObject $factory */
        $factory = $this->getMockBuilder(CleanupImporterFactory::class)
                        ->setMethods(['getRepositories'])
                        ->getMock();
        $factory->expects($this->once())
                ->method('getRepositories')
                ->with($container)
                ->willReturn($repositories);

        $factory($container, CleanupImporter::class);
    }

    /**
     * Tests the getRepositories method.
     * @throws ReflectionException
     * @covers ::getRepositories
     */
    public function testGetRepositories(): void
    {
        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);
        /* @var IconFileRepository $iconFileRepository */
        $iconFileRepository = $this->createMock(IconFileRepository::class);
        /* @var ItemRepository $itemRepository */
        $itemRepository = $this->createMock(ItemRepository::class);
        /* @var MachineRepository $machineRepository */
        $machineRepository = $this->createMock(MachineRepository::class);
        /* @var RecipeRepository $recipeRepository */
        $recipeRepository = $this->createMock(RecipeRepository::class);

        $expectedResult = [
            $craftingCategoryRepository,
            $iconFileRepository,
            $itemRepository,
            $machineRepository,
            $recipeRepository,
        ];

        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->exactly(5))
                  ->method('get')
                  ->withConsecutive(
                      [CraftingCategoryRepository::class],
                      [IconFileRepository::class],
                      [ItemRepository::class],
                      [MachineRepository::class],
                      [RecipeRepository::class]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $craftingCategoryRepository,
                      $iconFileRepository,
                      $itemRepository,
                      $machineRepository,
                      $recipeRepository
                  );

        $factory = new CleanupImporterFactory();
        $result = $this->invokeMethod($factory, 'getRepositories', $container);
        $this->assertEquals($expectedResult, $result);
    }
}
