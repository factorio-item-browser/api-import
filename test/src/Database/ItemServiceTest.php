<?php

namespace FactorioItemBrowserTest\Api\Import\Database;

use BluePsyduck\Common\Test\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Item;
use FactorioItemBrowser\Api\Database\Repository\ItemRepository;
use FactorioItemBrowser\Api\Import\Exception\MissingEntityException;
use FactorioItemBrowser\Api\Import\Database\ItemService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the ItemService class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Database\ItemService
 */
class ItemServiceTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var ItemRepository $itemRepository */
        $itemRepository = $this->createMock(ItemRepository::class);

        $service = new ItemService($itemRepository);

        $this->assertSame($itemRepository, $this->extractProperty($service, 'itemRepository'));
    }
    
    /**
     * Provides the data for the getByTypeAndName test.
     * @return array
     */
    public function provideGetByTypeAndName(): array
    {
        $item1 = new Item('abc', 'def');
        $item2 = new Item('ghi', 'jkl');

        return [
            [
                ['abc|def' => $item1, 'ghi|jkl' => $item2],
                'abc',
                'def',
                null,
                $item1,
                ['abc|def' => $item1, 'ghi|jkl' => $item2],
            ],
            [
                ['abc|def' => $item1],
                'ghi',
                'jkl',
                $item2,
                $item2,
                ['abc|def' => $item1, 'ghi|jkl' => $item2],
            ]
        ];
    }

    /**
     * Tests the getByTypeAndName method.
     * @param array $cache
     * @param string $type
     * @param string $name
     * @param Item|null $resultFetch
     * @param Item $expectedResult
     * @param array $expectedCache
     * @throws MissingEntityException
     * @throws ReflectionException
     * @covers ::getByTypeAndName
     * @dataProvider provideGetByTypeAndName
     */
    public function testGetByTypeAndName(
        array $cache,
        string $type,
        string $name,
        ?Item $resultFetch,
        Item $expectedResult,
        array $expectedCache
    ): void {
        /* @var ItemService|MockObject $service */
        $service = $this->getMockBuilder(ItemService::class)
                        ->setMethods(['fetchByTypeAndName'])
                        ->disableOriginalConstructor()
                        ->getMock();
        $service->expects($resultFetch === null ? $this->never() : $this->once())
                ->method('fetchByTypeAndName')
                ->with($type, $name)
                ->willReturn($resultFetch);
        $this->injectProperty($service, 'cache', $cache);

        $result = $service->getByTypeAndName($type, $name);

        $this->assertSame($expectedResult, $result);
        $this->assertEquals($expectedCache, $this->extractProperty($service, 'cache'));
    }
    
    
    /**
     * Provides the data for the fetchByTypeAndName test.
     * @return array
     */
    public function provideFetchByTypeAndName(): array
    {
        $item1 = new Item('abc', 'def');
        $item2 = new Item('ghi', 'jkl');

        return [
            [[$item1, $item2], false, $item1],
            [[], true, null]
        ];
    }

    /**
     * Tests the fetchByTypeAndName method.
     * @param array $resultFind
     * @param bool $expectException
     * @param Item|null $expectedResult
     * @throws ReflectionException
     * @covers ::fetchByTypeAndName
     * @dataProvider provideFetchByTypeAndName
     */
    public function testFetchByTypeAndName(array $resultFind, bool $expectException, ?Item $expectedResult): void
    {
        $type = 'foo';
        $name = 'bar';

        /* @var ItemRepository|MockObject $itemRepository */
        $itemRepository = $this->getMockBuilder(ItemRepository::class)
                               ->setMethods(['findByTypesAndNames'])
                               ->disableOriginalConstructor()
                               ->getMock();
        $itemRepository->expects($this->once())
                       ->method('findByTypesAndNames')
                       ->with([$type => [$name]])
                       ->willReturn($resultFind);

        if ($expectException) {
            $this->expectException(MissingEntityException::class);
        }

        $service = new ItemService($itemRepository);

        $result = $this->invokeMethod($service, 'fetchByTypeAndName', $type, $name);

        $this->assertSame($expectedResult, $result);
    }
}
