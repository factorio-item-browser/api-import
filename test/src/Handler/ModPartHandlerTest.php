<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Handler;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Exception\ErrorResponseException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Handler\ModPartHandler;
use FactorioItemBrowser\Api\Import\Importer\Mod\ModImporterInterface;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * The PHPUnit test of the ModPartHandler class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Handler\ModPartHandler
 */
class ModPartHandlerTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var ModImporterInterface $importer */
        $importer = $this->createMock(ModImporterInterface::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $handler = new ModPartHandler($importer, $modRepository, $registryService);

        $this->assertSame($importer, $this->extractProperty($handler, 'importer'));
        $this->assertSame($modRepository, $this->extractProperty($handler, 'modRepository'));
        $this->assertSame($registryService, $this->extractProperty($handler, 'registryService'));
    }

    /**
     * Tests the handle method.
     * @throws ImportException
     * @throws ReflectionException
     * @covers ::handle
     */
    public function testHandle(): void
    {
        $modName = 'abc';
        $exportMod = (new ExportMod())->setName('def');
        $databaseMod = new DatabaseMod('ghi');

        /* @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
                        ->setMethods(['getAttribute'])
                        ->getMockForAbstractClass();
        $request->expects($this->once())
                ->method('getAttribute')
                ->with('modName')
                ->willReturn($modName);

        /* @var ModImporterInterface|MockObject $importer */
        $importer = $this->getMockBuilder(ModImporterInterface::class)
                         ->setMethods(['import'])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('import')
                 ->with($exportMod, $databaseMod);

        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        /* @var ModPartHandler|MockObject $handler */
        $handler = $this->getMockBuilder(ModPartHandler::class)
                        ->setMethods(['fetchExportMod', 'fetchDatabaseMod'])
                        ->setConstructorArgs([$importer, $modRepository, $registryService])
                        ->getMock();
        $handler->expects($this->once())
                ->method('fetchExportMod')
                ->with($modName)
                ->willReturn($exportMod);
        $handler->expects($this->once())
                ->method('fetchDatabaseMod')
                ->with($exportMod)
                ->willReturn($databaseMod);

        $result = $handler->handle($request);
        $this->assertInstanceOf(EmptyResponse::class, $result);
    }

    /**
     * Provides the data for the fetchExportMod test.
     * @return array
     */
    public function provideFetchExportMod(): array
    {
        return [
            [false, false],
            [true, true],
        ];
    }

    /**
     * Tests the fetchExportMod method.
     * @param bool $throwException
     * @param bool $expectException
     * @throws ReflectionException
     * @covers ::fetchExportMod
     * @dataProvider provideFetchExportMod
     */
    public function testFetchExportMod(bool $throwException, bool $expectException): void
    {
        $modName = 'abc';
        $mod = (new ExportMod())->setName('def');

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getMod'])
                                ->disableOriginalConstructor()
                                ->getMock();
        if ($throwException) {
            $registryService->expects($this->once())
                            ->method('getMod')
                            ->with($modName)
                            ->willThrowException(new UnknownHashException('foo', 'bar'));
        } else {
            $registryService->expects($this->once())
                            ->method('getMod')
                            ->with($modName)
                            ->willReturn($mod);
        }

        if ($expectException) {
            $this->expectException(ErrorResponseException::class);
            $this->expectExceptionCode(404);
        }

        /* @var ModImporterInterface $importer */
        $importer = $this->createMock(ModImporterInterface::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        $handler = new ModPartHandler($importer, $modRepository, $registryService);
        $result = $this->invokeMethod($handler, 'fetchExportMod', $modName);

        $this->assertSame($mod, $result);
    }

    /**
     * Provides the data for the fetchDatabaseMod test.
     * @return array
     */
    public function provideFetchDatabaseMod(): array
    {
        $databaseMod1 = new DatabaseMod('abc');
        $databaseMod2 = new DatabaseMod('def');

        return [
            [[$databaseMod1, $databaseMod2], false, $databaseMod1],
            [[], true, null]
        ];
    }

    /**
     * Tests the fetchDatabaseMod method.
     * @param array $resultFind
     * @param bool $expectException
     * @param DatabaseMod|null $expectedResult
     * @throws ReflectionException
     * @covers ::fetchDatabaseMod
     * @dataProvider provideFetchDatabaseMod
     */
    public function testFetchDatabaseMod(
        array $resultFind,
        bool $expectException,
        ?DatabaseMod $expectedResult
    ): void {
        $name = 'foo';
        $exportMod = (new ExportMod())->setName($name);

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
            $this->expectException(ErrorResponseException::class);
            $this->expectExceptionCode(400);
        }

        /* @var ModImporterInterface $importer */
        $importer = $this->createMock(ModImporterInterface::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $handler = new ModPartHandler($importer, $modRepository, $registryService);
        $result = $this->invokeMethod($handler, 'fetchDatabaseMod', $exportMod);

        $this->assertSame($expectedResult, $result);
    }
}
