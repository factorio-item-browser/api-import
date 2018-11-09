<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Handler;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Repository\ModCombinationRepository;
use FactorioItemBrowser\Api\Import\Constant\ServiceName;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Handler\AbstractCombinationPartHandlerFactory;
use FactorioItemBrowser\Api\Import\Importer\Combination\CraftingCategoryImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\IconImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\CombinationImporterInterface;
use FactorioItemBrowser\Api\Import\Importer\Combination\ItemImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\MachineImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\RecipeImporter;
use FactorioItemBrowser\Api\Import\Importer\Combination\TranslationImporter;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the AbstractCombinationPartHandlerFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Handler\AbstractCombinationPartHandlerFactory
 */
class AbstractCombinationPartHandlerFactoryTest extends TestCase
{
    /**
     * Provides the data for the canCreate test.
     * @return array
     */
    public function provideCanCreate(): array
    {
        return [
            [ServiceName::COMBINATION_CRAFTING_CATEGORIES_HANDLER, true],
            [ServiceName::COMBINATION_ICONS_HANDLER, true],
            [ServiceName::COMBINATION_ITEMS_HANDLER, true],
            [ServiceName::COMBINATION_MACHINES_HANDLER, true],
            [ServiceName::COMBINATION_RECIPES_HANDLER, true],
            [ServiceName::COMBINATION_TRANSLATIONS_HANDLER, true],
            ['foo', false],
        ];
    }

    /**
     * Tests the canCreate method.
     * @param string $requestedName
     * @param bool $expectedResult
     * @covers ::canCreate
     * @dataProvider provideCanCreate
     */
    public function testCanCreate(string $requestedName, bool $expectedResult): void
    {
        /* @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $factory = new AbstractCombinationPartHandlerFactory();
        $result = $factory->canCreate($container, $requestedName);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the invoke test.
     * @return array
     */
    public function provideInvoke(): array
    {
        return [
            [ServiceName::COMBINATION_CRAFTING_CATEGORIES_HANDLER, CraftingCategoryImporter::class],
            [ServiceName::COMBINATION_ICONS_HANDLER, IconImporter::class],
            [ServiceName::COMBINATION_ITEMS_HANDLER, ItemImporter::class],
            [ServiceName::COMBINATION_MACHINES_HANDLER, MachineImporter::class],
            [ServiceName::COMBINATION_RECIPES_HANDLER, RecipeImporter::class],
            [ServiceName::COMBINATION_TRANSLATIONS_HANDLER, TranslationImporter::class],
        ];
    }

    /**
     * Tests the invoking.
     * @param string $requestedName
     * @param string $expectedImporterClass
     * @covers ::__invoke
     * @dataProvider provideInvoke
     */
    public function testInvoke(string $requestedName, string $expectedImporterClass): void
    {
        /* @var ModCombinationRepository $modCombinationRepository*/
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);

        /* @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
                              ->setMethods(['getRepository'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $entityManager->expects($this->once())
                      ->method('getRepository')
                      ->with(ModCombination::class)
                      ->willReturn($modCombinationRepository);

        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->exactly(3))
                  ->method('get')
                  ->withConsecutive(
                      [EntityManager::class],
                      [RegistryService::class],
                      [$expectedImporterClass]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $entityManager,
                      $this->createMock(RegistryService::class),
                      $this->createMock(CombinationImporterInterface::class)
                  );

        $factory = new AbstractCombinationPartHandlerFactory();
        $factory($container, $requestedName);
    }
}
