<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Database;

use BluePsyduck\Common\Test\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\CraftingCategory;
use FactorioItemBrowser\Api\Database\Repository\CraftingCategoryRepository;
use FactorioItemBrowser\Api\Import\Exception\MissingEntityException;
use FactorioItemBrowser\Api\Import\Database\CraftingCategoryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the CraftingCategoryService class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Database\CraftingCategoryService
 */
class CraftingCategoryServiceTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var CraftingCategoryRepository $craftingCategoryRepository */
        $craftingCategoryRepository = $this->createMock(CraftingCategoryRepository::class);

        $service = new CraftingCategoryService($craftingCategoryRepository);

        $this->assertSame($craftingCategoryRepository, $this->extractProperty($service, 'craftingCategoryRepository'));
    }

    /**
     * Provides the data for the getByName test.
     * @return array
     */
    public function provideGetByName(): array
    {
        $category1 = new CraftingCategory('abc');
        $category2 = new CraftingCategory('def');

        return [
            [
                ['abc' => $category1, 'def' => $category2],
                'abc',
                null,
                $category1,
                ['abc' => $category1, 'def' => $category2],
            ],
            [
                ['abc' => $category1],
                'def',
                $category2,
                $category2,
                ['abc' => $category1, 'def' => $category2],
            ]
        ];
    }

    /**
     * Tests the getByName method.
     * @param array $cache
     * @param string $name
     * @param CraftingCategory|null $resultFetch
     * @param CraftingCategory $expectedResult
     * @param array $expectedCache
     * @throws MissingEntityException
     * @throws ReflectionException
     * @covers ::getByName
     * @dataProvider provideGetByName
     */
    public function testGetByName(
        array $cache,
        string $name,
        ?CraftingCategory $resultFetch,
        CraftingCategory $expectedResult,
        array $expectedCache
    ): void {
        /* @var CraftingCategoryService|MockObject $service */
        $service = $this->getMockBuilder(CraftingCategoryService::class)
                        ->setMethods(['fetchByName'])
                        ->disableOriginalConstructor()
                        ->getMock();
        $service->expects($resultFetch === null ? $this->never() : $this->once())
                ->method('fetchByName')
                ->with($name)
                ->willReturn($resultFetch);
        $this->injectProperty($service, 'cache', $cache);

        $result = $service->getByName($name);

        $this->assertSame($expectedResult, $result);
        $this->assertEquals($expectedCache, $this->extractProperty($service, 'cache'));
    }

    /**
     * Provides the data for the fetchByName test.
     * @return array
     */
    public function provideFetchByName(): array
    {
        $category1 = new CraftingCategory('abc');
        $category2 = new CraftingCategory('def');

        return [
            [[$category1, $category2], false, $category1],
            [[], true, null]
        ];
    }

    /**
     * Tests the fetchByName method.
     * @param array $resultFind
     * @param bool $expectException
     * @param CraftingCategory|null $expectedResult
     * @throws ReflectionException
     * @covers ::fetchByName
     * @dataProvider provideFetchByName
     */
    public function testFetchByName(array $resultFind, bool $expectException, ?CraftingCategory $expectedResult): void
    {
        $name =  'foo';

        /* @var CraftingCategoryRepository|MockObject $craftingCategoryRepository */
        $craftingCategoryRepository = $this->getMockBuilder(CraftingCategoryRepository::class)
                                           ->setMethods(['findByNames'])
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $craftingCategoryRepository->expects($this->once())
                                   ->method('findByNames')
                                   ->with([$name])
                                   ->willReturn($resultFind);

        if ($expectException) {
            $this->expectException(MissingEntityException::class);
        }

        $service = new CraftingCategoryService($craftingCategoryRepository);

        $result = $this->invokeMethod($service, 'fetchByName', $name);

        $this->assertSame($expectedResult, $result);
    }
}
