<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\ExportData;

use BluePsyduck\Common\Test\ReflectionTrait;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\ExportData\Entity\Icon;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\Registry\ContentRegistry;
use FactorioItemBrowser\ExportData\Registry\EntityRegistry;
use FactorioItemBrowser\ExportData\Registry\ModRegistry;
use FactorioItemBrowser\ExportData\Service\ExportDataService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the RegistryService class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\ExportData\RegistryService
 */
class RegistryServiceTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var ExportDataService $exportDataService */
        $exportDataService = $this->createMock(ExportDataService::class);

        $service = new RegistryService($exportDataService);

        $this->assertSame($exportDataService, $this->extractProperty($service, 'exportDataService'));
    }

    /**
     * Provides the data for the getCombination test.
     * @return array
     */
    public function provideGetCombination(): array
    {
        $combination = (new Combination())->setName('foo');

        return [
            [$combination, false, $combination],
            [null, true, null],
        ];
    }

    /**
     * Tests the getCombination method.
     * @param Combination|null $combination
     * @param bool $expectException
     * @param Combination|null $expectedResult
     * @throws UnknownHashException
     * @covers ::getCombination
     * @dataProvider provideGetCombination
     */
    public function testGetCombination(
        ?Combination $combination,
        bool $expectException,
        ?Combination $expectedResult
    ): void {
        $combinationHash = 'abc';

        /* @var EntityRegistry|MockObject $combinationRegistry */
        $combinationRegistry = $this->getMockBuilder(EntityRegistry::class)
                             ->setMethods(['get'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $combinationRegistry->expects($this->once())
                     ->method('get')
                     ->with($combinationHash)
                     ->willReturn($combination);

        /* @var ExportDataService|MockObject $exportDataService */
        $exportDataService = $this->getMockBuilder(ExportDataService::class)
                                  ->setMethods(['getCombinationRegistry'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportDataService->expects($this->once())
                          ->method('getCombinationRegistry')
                          ->willReturn($combinationRegistry);

        if ($expectException) {
            $this->expectException(UnknownHashException::class);
        }

        $service = new RegistryService($exportDataService);

        $result = $service->getCombination($combinationHash);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the getMod test.
     * @return array
     */
    public function provideGetMod(): array
    {
        $mod = (new Mod())->setName('foo');

        return [
            [$mod, false, $mod],
            [null, true, null],
        ];
    }

    /**
     * Tests the getMod method.
     * @param Mod|null $mod
     * @param bool $expectException
     * @param Mod|null $expectedResult
     * @throws UnknownHashException
     * @covers ::getMod
     * @dataProvider provideGetMod
     */
    public function testGetMod(
        ?Mod $mod,
        bool $expectException,
        ?Mod $expectedResult
    ): void {
        $modName = 'abc';

        /* @var ModRegistry|MockObject $modRegistry */
        $modRegistry = $this->getMockBuilder(ModRegistry::class)
                             ->setMethods(['get'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $modRegistry->expects($this->once())
                     ->method('get')
                     ->with($modName)
                     ->willReturn($mod);

        /* @var ExportDataService|MockObject $exportDataService */
        $exportDataService = $this->getMockBuilder(ExportDataService::class)
                                  ->setMethods(['getModRegistry'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportDataService->expects($this->once())
                          ->method('getModRegistry')
                          ->willReturn($modRegistry);

        if ($expectException) {
            $this->expectException(UnknownHashException::class);
        }

        $service = new RegistryService($exportDataService);

        $result = $service->getMod($modName);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the getIcon test.
     * @return array
     */
    public function provideGetIcon(): array
    {
        $icon = (new Icon())->setSize(36);

        return [
            [$icon, false, $icon],
            [null, true, null],
        ];
    }

    /**
     * Tests the getIcon method.
     * @param Icon|null $icon
     * @param bool $expectException
     * @param Icon|null $expectedResult
     * @throws UnknownHashException
     * @covers ::getIcon
     * @dataProvider provideGetIcon
     */
    public function testGetIcon(?Icon $icon, bool $expectException, ?Icon $expectedResult): void
    {
        $iconHash = 'abc';

        /* @var EntityRegistry|MockObject $iconRegistry */
        $iconRegistry = $this->getMockBuilder(EntityRegistry::class)
                             ->setMethods(['get'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $iconRegistry->expects($this->once())
                     ->method('get')
                     ->with($iconHash)
                     ->willReturn($icon);

        /* @var ExportDataService|MockObject $exportDataService */
        $exportDataService = $this->getMockBuilder(ExportDataService::class)
                                  ->setMethods(['getIconRegistry'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportDataService->expects($this->once())
                          ->method('getIconRegistry')
                          ->willReturn($iconRegistry);

        if ($expectException) {
            $this->expectException(UnknownHashException::class);
        }

        $service = new RegistryService($exportDataService);

        $result = $service->getIcon($iconHash);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the getItem test.
     * @return array
     */
    public function provideGetItem(): array
    {
        $item = (new Item())->setName('foo');

        return [
            [$item, false, $item],
            [null, true, null],
        ];
    }

    /**
     * Tests the getItem method.
     * @param Item|null $item
     * @param bool $expectException
     * @param Item|null $expectedResult
     * @throws UnknownHashException
     * @covers ::getItem
     * @dataProvider provideGetItem
     */
    public function testGetItem(?Item $item, bool $expectException, ?Item $expectedResult): void
    {
        $itemHash = 'abc';

        /* @var EntityRegistry|MockObject $itemRegistry */
        $itemRegistry = $this->getMockBuilder(EntityRegistry::class)
                             ->setMethods(['get'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $itemRegistry->expects($this->once())
                     ->method('get')
                     ->with($itemHash)
                     ->willReturn($item);

        /* @var ExportDataService|MockObject $exportDataService */
        $exportDataService = $this->getMockBuilder(ExportDataService::class)
                                  ->setMethods(['getItemRegistry'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportDataService->expects($this->once())
                          ->method('getItemRegistry')
                          ->willReturn($itemRegistry);

        if ($expectException) {
            $this->expectException(UnknownHashException::class);
        }

        $service = new RegistryService($exportDataService);

        $result = $service->getItem($itemHash);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the getMachine test.
     * @return array
     */
    public function provideGetMachine(): array
    {
        $machine = (new Machine())->setName('foo');

        return [
            [$machine, false, $machine],
            [null, true, null],
        ];
    }

    /**
     * Tests the getMachine method.
     * @param Machine|null $machine
     * @param bool $expectException
     * @param Machine|null $expectedResult
     * @throws UnknownHashException
     * @covers ::getMachine
     * @dataProvider provideGetMachine
     */
    public function testGetMachine(?Machine $machine, bool $expectException, ?Machine $expectedResult): void
    {
        $machineHash = 'abc';

        /* @var EntityRegistry|MockObject $machineRegistry */
        $machineRegistry = $this->getMockBuilder(EntityRegistry::class)
                                ->setMethods(['get'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $machineRegistry->expects($this->once())
                        ->method('get')
                        ->with($machineHash)
                        ->willReturn($machine);

        /* @var ExportDataService|MockObject $exportDataService */
        $exportDataService = $this->getMockBuilder(ExportDataService::class)
                                  ->setMethods(['getMachineRegistry'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportDataService->expects($this->once())
                          ->method('getMachineRegistry')
                          ->willReturn($machineRegistry);

        if ($expectException) {
            $this->expectException(UnknownHashException::class);
        }

        $service = new RegistryService($exportDataService);

        $result = $service->getMachine($machineHash);
        $this->assertSame($expectedResult, $result);
    }
    
    /**
     * Provides the data for the getRecipe test.
     * @return array
     */
    public function provideGetRecipe(): array
    {
        $recipe = (new Recipe())->setName('foo');

        return [
            [$recipe, false, $recipe],
            [null, true, null],
        ];
    }

    /**
     * Tests the getRecipe method.
     * @param Recipe|null $recipe
     * @param bool $expectException
     * @param Recipe|null $expectedResult
     * @throws UnknownHashException
     * @covers ::getRecipe
     * @dataProvider provideGetRecipe
     */
    public function testGetRecipe(?Recipe $recipe, bool $expectException, ?Recipe $expectedResult): void
    {
        $recipeHash = 'abc';

        /* @var EntityRegistry|MockObject $recipeRegistry */
        $recipeRegistry = $this->getMockBuilder(EntityRegistry::class)
                               ->setMethods(['get'])
                               ->disableOriginalConstructor()
                               ->getMock();
        $recipeRegistry->expects($this->once())
                       ->method('get')
                       ->with($recipeHash)
                       ->willReturn($recipe);

        /* @var ExportDataService|MockObject $exportDataService */
        $exportDataService = $this->getMockBuilder(ExportDataService::class)
                                  ->setMethods(['getRecipeRegistry'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportDataService->expects($this->once())
                          ->method('getRecipeRegistry')
                          ->willReturn($recipeRegistry);

        if ($expectException) {
            $this->expectException(UnknownHashException::class);
        }

        $service = new RegistryService($exportDataService);

        $result = $service->getRecipe($recipeHash);
        $this->assertSame($expectedResult, $result);
    }
    
    
    /**
     * Provides the data for the getRenderedIcon test.
     * @return array
     */
    public function provideGetRenderedIcon(): array
    {
        return [
            ['foo', false, 'foo'],
            [null, true, null],
        ];
    }

    /**
     * Tests the getRenderedIcon method.
     * @param string|null $renderedIcon
     * @param bool $expectException
     * @param string|null $expectedResult
     * @throws UnknownHashException
     * @covers ::getRenderedIcon
     * @dataProvider provideGetRenderedIcon
     */
    public function testGetRenderedIcon(?string $renderedIcon, bool $expectException, ?string $expectedResult): void
    {
        $renderedIconHash = 'abc';

        /* @var ContentRegistry|MockObject $renderedIconRegistry */
        $renderedIconRegistry = $this->getMockBuilder(ContentRegistry::class)
                             ->setMethods(['get'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $renderedIconRegistry->expects($this->once())
                     ->method('get')
                     ->with($renderedIconHash)
                     ->willReturn($renderedIcon);

        /* @var ExportDataService|MockObject $exportDataService */
        $exportDataService = $this->getMockBuilder(ExportDataService::class)
                                  ->setMethods(['getRenderedIconRegistry'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $exportDataService->expects($this->once())
                          ->method('getRenderedIconRegistry')
                          ->willReturn($renderedIconRegistry);

        if ($expectException) {
            $this->expectException(UnknownHashException::class);
        }

        $service = new RegistryService($exportDataService);

        $result = $service->getRenderedIcon($renderedIconHash);
        $this->assertSame($expectedResult, $result);
    }
}
