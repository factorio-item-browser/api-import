<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Generic\ModOrderImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the ModOrderImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\ModOrderImporter
 */
class ModOrderImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new ModOrderImporter($entityManager, $modRepository, $registryService);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($modRepository, $this->extractProperty($importer, 'modRepository'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        $orderedMods = [
            $this->createMock(DatabaseMod::class),
            $this->createMock(DatabaseMod::class),
        ];

        /* @var ModOrderImporter|MockObject $importer */
        $importer = $this->getMockBuilder(ModOrderImporter::class)
                         ->setMethods(['getOrderedMods', 'assignOrder', 'flushEntities'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getOrderedMods')
                 ->willReturn($orderedMods);
        $importer->expects($this->once())
                 ->method('assignOrder')
                 ->with($orderedMods);
        $importer->expects($this->once())
                 ->method('flushEntities');

        $importer->import();
    }

    /**
     * Tests the getOrderedMods method.
     * @throws ReflectionException
     * @covers ::getOrderedMods
     */
    public function testGetOrderedMods(): void
    {
        $mod1 = new DatabaseMod('abc');
        $mod2 = new DatabaseMod('def');
        $mods = [$mod1, $mod2];

        /* @var ModRepository|MockObject $modRepository */
        $modRepository = $this->getMockBuilder(ModRepository::class)
                              ->setMethods(['findAll'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $modRepository->expects($this->once())
                      ->method('findAll')
                      ->willReturn($mods);

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        /* @var ModOrderImporter|MockObject $importer */
        $importer = $this->getMockBuilder(ModOrderImporter::class)
                         ->setMethods(['compareMods'])
                         ->setConstructorArgs([$entityManager, $modRepository, $registryService])
                         ->getMock();
        $importer->expects($this->atLeast(1))
                 ->method('compareMods')
                 ->with($this->isInstanceOf(DatabaseMod::class), $this->isInstanceOf(DatabaseMod::class))
                 ->willReturn(0);

        $result = $this->invokeMethod($importer, 'getOrderedMods');

        $this->assertEquals($mods, $result);
    }

    /**
     * Provides the data for the compareMods test.
     * @return array
     */
    public function provideCompareMods(): array
    {
        return [
            [42, 1337, -1],
            [1337, 42, 1],
            [42, 42, 0],
        ];
    }

    /**
     * Tests the compareMods method.
     * @param int $leftOrder
     * @param int $rightOrder
     * @param int $expectedResult
     * @throws ReflectionException
     * @covers ::compareMods
     * @dataProvider provideCompareMods
     */
    public function testCompareMods(int $leftOrder, int $rightOrder, int $expectedResult): void
    {
        $leftMod = new DatabaseMod('abc');
        $rightMod = new DatabaseMod('def');

        /* @var ModOrderImporter|MockObject $importer */
        $importer = $this->getMockBuilder(ModOrderImporter::class)
                         ->setMethods(['getOrderByModName'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->exactly(2))
                 ->method('getOrderByModName')
                 ->withConsecutive(
                     ['abc'],
                     ['def']
                 )
                 ->willReturnOnConsecutiveCalls(
                     $leftOrder,
                     $rightOrder
                 );

        $result = $this->invokeMethod($importer, 'compareMods', $leftMod, $rightMod);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getOrderByModName method.
     * @throws ReflectionException
     * @covers ::getOrderByModName
     */
    public function testGetOrderByModName(): void
    {
        $modName = 'abc';
        $order = 42;
        $mod = new ExportMod();
        $mod->setName($modName)
            ->setOrder($order);

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getMod'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->once())
                        ->method('getMod')
                        ->with($modName)
                        ->willReturn($mod);

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        $importer = new ModOrderImporter($entityManager, $modRepository, $registryService);
        $result = $this->invokeMethod($importer, 'getOrderByModName', $modName);

        $this->assertSame($order, $result);
    }

    /**
     * Tests the assignOrder method.
     * @throws ReflectionException
     * @covers ::assignOrder
     */
    public function testAssignOrder(): void
    {
        /* @var DatabaseMod|MockObject $mod1 */
        $mod1 = $this->getMockBuilder(DatabaseMod::class)
                     ->setMethods(['setOrder'])
                     ->disableOriginalConstructor()
                     ->getMock();
        $mod1->expects($this->once())
             ->method('setOrder')
             ->with(1);

        /* @var DatabaseMod|MockObject $mod2 */
        $mod2 = $this->getMockBuilder(DatabaseMod::class)
                     ->setMethods(['setOrder'])
                     ->disableOriginalConstructor()
                     ->getMock();
        $mod2->expects($this->once())
             ->method('setOrder')
             ->with(2);

        $orderedMods = [$mod1, $mod2];

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new ModOrderImporter($entityManager, $modRepository, $registryService);
        $this->invokeMethod($importer, 'assignOrder', $orderedMods);
    }
}
