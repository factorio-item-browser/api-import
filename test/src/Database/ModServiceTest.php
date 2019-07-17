<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Database;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Database\ModService;
use FactorioItemBrowser\Api\Import\Exception\MissingEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the ModService class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Database\ModService
 */
class ModServiceTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        $service = new ModService($modRepository);

        $this->assertSame($modRepository, $this->extractProperty($service, 'modRepository'));
    }
    
    /**
     * Provides the data for the getByName test.
     * @return array
     */
    public function provideGetByName(): array
    {
        $mod1 = new Mod('abc');
        $mod2 = new Mod('def');

        return [
            [
                ['abc' => $mod1, 'def' => $mod2],
                'abc',
                null,
                $mod1,
                ['abc' => $mod1, 'def' => $mod2],
            ],
            [
                ['abc' => $mod1],
                'def',
                $mod2,
                $mod2,
                ['abc' => $mod1, 'def' => $mod2],
            ]
        ];
    }

    /**
     * Tests the getByName method.
     * @param array $cache
     * @param string $name
     * @param Mod|null $resultFetch
     * @param Mod $expectedResult
     * @param array $expectedCache
     * @throws MissingEntityException
     * @throws ReflectionException
     * @covers ::getByName
     * @dataProvider provideGetByName
     */
    public function testGetByName(
        array $cache,
        string $name,
        ?Mod $resultFetch,
        Mod $expectedResult,
        array $expectedCache
    ): void {
        /* @var ModService|MockObject $service */
        $service = $this->getMockBuilder(ModService::class)
                        ->setMethods(['fetchByName'])
                        ->disableOriginalConstructor()
                        ->getMock();
        if ($resultFetch === null) {
            $service->expects($this->never())
                    ->method('fetchByName');
        } else {
            $service->expects($this->once())
                    ->method('fetchByName')
                    ->with($name)
                    ->willReturn($resultFetch);
        }
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
        $mod1 = new Mod('abc');
        $mod2 = new Mod('def');

        return [
            [[$mod1, $mod2], false, $mod1],
            [[], true, null]
        ];
    }

    /**
     * Tests the fetchByName method.
     * @param array $resultFind
     * @param bool $expectException
     * @param Mod|null $expectedResult
     * @throws ReflectionException
     * @covers ::fetchByName
     * @dataProvider provideFetchByName
     */
    public function testFetchByName(array $resultFind, bool $expectException, ?Mod $expectedResult): void
    {
        $name = 'foo';

        /* @var ModRepository|MockObject $modRepository */
        $modRepository = $this->getMockBuilder(ModRepository::class)
                               ->setMethods(['findByNamesWithDependencies'])
                               ->disableOriginalConstructor()
                               ->getMock();
        $modRepository->expects($this->once())
                       ->method('findByNamesWithDependencies')
                       ->with([$name])
                       ->willReturn($resultFind);

        if ($expectException) {
            $this->expectException(MissingEntityException::class);
        }

        $service = new ModService($modRepository);

        $result = $this->invokeMethod($service, 'fetchByName', $name);

        $this->assertSame($expectedResult, $result);
    }
}
