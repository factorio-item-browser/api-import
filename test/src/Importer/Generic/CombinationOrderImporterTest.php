<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Repository\ModCombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\Generic\CombinationOrderImporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the CombinationOrderImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\CombinationOrderImporter
 */
class CombinationOrderImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        $importer = new CombinationOrderImporter($entityManager, $modCombinationRepository, $modRepository);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($modCombinationRepository, $this->extractProperty($importer, 'modCombinationRepository'));
        $this->assertSame($modRepository, $this->extractProperty($importer, 'modRepository'));
    }

    /**
     * Tests the import method.
     * @throws ImportException
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        $modOrders = [42 => 1, 1337 => 2];
        $orderedCombinations = [
            $this->createMock(ModCombination::class),
            $this->createMock(ModCombination::class),
        ];

        /* @var CombinationOrderImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CombinationOrderImporter::class)
                         ->setMethods(['getModOrders', 'getOrderedCombinations', 'assignOrder', 'flushEntities'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getModOrders')
                 ->willReturn($modOrders);
        $importer->expects($this->once())
                 ->method('getOrderedCombinations')
                 ->willReturn($orderedCombinations);
        $importer->expects($this->once())
                 ->method('assignOrder')
                 ->with($orderedCombinations);
        $importer->expects($this->once())
                 ->method('flushEntities');

        $importer->import();
        $this->assertSame($modOrders, $this->extractProperty($importer, 'modOrders'));
    }

    /**
     * Tests the getModOrders method.
     * @throws ReflectionException
     * @covers ::getModOrders
     */
    public function testGetModOrders(): void
    {
        $mod1 = new Mod('abc');
        $mod1->setId(42)
             ->setOrder(1);
        $mod2 = new Mod('def');
        $mod2->setId(1337)
             ->setOrder(2);

        $expectedResult = [
            42 => 1,
            1337 => 2,
        ];

        /* @var ModRepository|MockObject $modRepository */
        $modRepository = $this->getMockBuilder(ModRepository::class)
                              ->setMethods(['findAll'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $modRepository->expects($this->once())
                      ->method('findAll')
                      ->willReturn([$mod1, $mod2]);

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);

        $importer = new CombinationOrderImporter($entityManager, $modCombinationRepository, $modRepository);
        $result = $this->invokeMethod($importer, 'getModOrders');

        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Tests the getOrderedCombinations method.
     * @throws ReflectionException
     * @covers ::getOrderedCombinations
     */
    public function testGetOrderedCombinations(): void
    {
        /* @var ModCombination $combination1 */
        $combination1 = $this->createMock(ModCombination::class);
        /* @var ModCombination $combination2 */
        $combination2 = $this->createMock(ModCombination::class);
        $combinations = [$combination1, $combination2];

        /* @var ModCombinationRepository|MockObject $modCombinationRepository */
        $modCombinationRepository = $this->getMockBuilder(ModCombinationRepository::class)
                                         ->setMethods(['findAll'])
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $modCombinationRepository->expects($this->once())
                                 ->method('findAll')
                                 ->willReturn($combinations);

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        /* @var CombinationOrderImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CombinationOrderImporter::class)
                         ->setMethods(['compareCombinations'])
                         ->setConstructorArgs([$entityManager, $modCombinationRepository, $modRepository])
                         ->getMock();
        $importer->expects($this->atLeast(1))
                 ->method('compareCombinations')
                 ->with($this->isInstanceOf(ModCombination::class), $this->isInstanceOf(ModCombination::class))
                 ->willReturn(0);

        $result = $this->invokeMethod($importer, 'getOrderedCombinations');

        $this->assertEquals($combinations, $result);
    }

    /**
     * Provides the data for the compareCombinations test.
     * @return array
     */
    public function provideCompareCombinations(): array
    {
        $modOrders = [
            21 => 12,
            32 => 23,
            43 => 34,
            54 => 45,
        ];

        return [
            // Main mod decides order.
            [$modOrders, 21, 32, null, null, -1],
            [$modOrders, 32, 21, null, null, 1],

            // Number of optional mods decides order.
            [$modOrders, 21, 21, [32], [32, 43], -1],
            [$modOrders, 21, 21, [32, 43], [32], 1],

            // Order of optional mods decide order.
            [$modOrders, 21, 21, [32, 43], [32, 54], -1],
            [$modOrders, 21, 21, [32, 54], [32, 43], 1],
        ];
    }

    /**
     * Tests the compareCombinations method.
     * @param array $modOrders
     * @param int $leftModId
     * @param int $rightModId
     * @param array|null $leftOrders
     * @param array|null $rightOrders
     * @param int $expectedResult
     * @throws ReflectionException
     * @covers ::compareCombinations
     * @dataProvider provideCompareCombinations
     */
    public function testCompareCombinations(
        array $modOrders,
        int $leftModId,
        int $rightModId,
        ?array $leftOrders,
        ?array $rightOrders,
        int $expectedResult
    ): void {
        $leftOptionalModIds = [42, 1337];
        $leftMod = new Mod('abc');
        $leftMod->setId($leftModId);
        $leftCombination = new ModCombination($leftMod, 'def');
        $leftCombination->setOptionalModIds($leftOptionalModIds);

        $rightOptionalModIds = [21, 7331];
        $rightMod = new Mod('ghi');
        $rightMod->setId($rightModId);
        $rightCombination = new ModCombination($rightMod, 'jkl');
        $rightCombination->setOptionalModIds($rightOptionalModIds);

        /* @var CombinationOrderImporter|MockObject $importer */
        $importer = $this->getMockBuilder(CombinationOrderImporter::class)
                         ->setMethods(['mapModIdsToOrders'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($leftOrders === null ? $this->never() : $this->exactly(2))
                 ->method('mapModIdsToOrders')
                 ->withConsecutive(
                     [$leftOptionalModIds],
                     [$rightOptionalModIds]
                 )
                 ->willReturnOnConsecutiveCalls(
                     $leftOrders,
                     $rightOrders
                 );
        $this->injectProperty($importer, 'modOrders', $modOrders);
        $result = $this->invokeMethod($importer, 'compareCombinations', $leftCombination, $rightCombination);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the mapModIdsToOrders method.
     * @throws ReflectionException
     * @covers ::mapModIdsToOrders
     */
    public function testMapModIdsToOrders(): void
    {
        $modOrders = [
            42 => 1,
            1337 => 2,
            21 => 3,
        ];
        $modIds = [21, 42];
        $expectedResult = [1, 3];

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        $importer = new CombinationOrderImporter($entityManager, $modCombinationRepository, $modRepository);
        $this->injectProperty($importer, 'modOrders', $modOrders);
        $result = $this->invokeMethod($importer, 'mapModIdsToOrders', $modIds);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the assignOrder method.
     * @throws ReflectionException
     * @covers ::assignOrder
     */
    public function testAssignOrder(): void
    {
        /* @var ModCombination|MockObject $combination1 */
        $combination1 = $this->getMockBuilder(ModCombination::class)
                             ->setMethods(['setOrder'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $combination1->expects($this->once())
                     ->method('setOrder')
                     ->with(1);

        /* @var ModCombination|MockObject $combination2 */
        $combination2 = $this->getMockBuilder(ModCombination::class)
                             ->setMethods(['setOrder'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $combination2->expects($this->once())
                     ->method('setOrder')
                     ->with(2);

        $orderedCombinations = [$combination1, $combination2];

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        $importer = new CombinationOrderImporter($entityManager, $modCombinationRepository, $modRepository);
        $this->invokeMethod($importer, 'assignOrder', $orderedCombinations);
    }
}
