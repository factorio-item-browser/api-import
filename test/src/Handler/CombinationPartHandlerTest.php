<?php

namespace FactorioItemBrowserTest\Api\Import\Handler;

use BluePsyduck\Common\Test\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Repository\ModCombinationRepository;
use FactorioItemBrowser\Api\Import\Exception\ErrorResponseException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Handler\CombinationPartHandler;
use FactorioItemBrowser\Api\Import\Importer\Combination\CombinationImporterInterface;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * The PHPUnit test of the CombinationPartHandler class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Handler\CombinationPartHandler
 */
class CombinationPartHandlerTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var CombinationImporterInterface $importer */
        $importer = $this->createMock(CombinationImporterInterface::class);
        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $handler = new CombinationPartHandler($importer, $modCombinationRepository, $registryService);

        $this->assertSame($importer, $this->extractProperty($handler, 'importer'));
        $this->assertSame($modCombinationRepository, $this->extractProperty($handler, 'modCombinationRepository'));
        $this->assertSame($registryService, $this->extractProperty($handler, 'registryService'));
    }

    /**
     * Tests the handle method.
     * @throws ImportException
     * @covers ::handle
     */
    public function testHandle(): void
    {
        $combinationHash = 'abc';
        $exportCombination = (new ExportCombination())->setName('def');
        $databaseCombination = new DatabaseCombination(new Mod('ghi'), 'jkl');

        /* @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
                        ->setMethods(['getAttribute'])
                        ->getMockForAbstractClass();
        $request->expects($this->once())
                ->method('getAttribute')
                ->with('combinationHash')
                ->willReturn($combinationHash);

        /* @var CombinationImporterInterface|MockObject $importer */
        $importer = $this->getMockBuilder(CombinationImporterInterface::class)
                         ->setMethods(['import'])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('import')
                 ->with($exportCombination, $databaseCombination);

        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        /* @var CombinationPartHandler|MockObject $handler */
        $handler = $this->getMockBuilder(CombinationPartHandler::class)
                        ->setMethods(['fetchExportCombination', 'fetchDatabaseCombination'])
                        ->setConstructorArgs([$importer, $modCombinationRepository, $registryService])
                        ->getMock();
        $handler->expects($this->once())
                ->method('fetchExportCombination')
                ->with($combinationHash)
                ->willReturn($exportCombination);
        $handler->expects($this->once())
                ->method('fetchDatabaseCombination')
                ->with($exportCombination)
                ->willReturn($databaseCombination);

        $result = $handler->handle($request);
        $this->assertInstanceOf(EmptyResponse::class, $result);
    }

    /**
     * Provides the data for the fetchExportCombination test.
     * @return array
     */
    public function provideFetchExportCombination(): array
    {
        return [
            [false, false],
            [true, true],
        ];
    }

    /**
     * Tests the fetchExportCombination method.
     * @param bool $throwException
     * @param bool $expectException
     * @throws ReflectionException
     * @covers ::fetchExportCombination
     * @dataProvider provideFetchExportCombination
     */
    public function testFetchExportCombination(bool $throwException, bool $expectException): void
    {
        $combinationHash = 'abc';
        $combination = (new ExportCombination())->setName('def');

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getCombination'])
                                ->disableOriginalConstructor()
                                ->getMock();
        if ($throwException) {
            $registryService->expects($this->once())
                            ->method('getCombination')
                            ->with($combinationHash)
                            ->willThrowException(new UnknownHashException('foo', 'bar'));
        } else {
            $registryService->expects($this->once())
                            ->method('getCombination')
                            ->with($combinationHash)
                            ->willReturn($combination);
        }

        if ($expectException) {
            $this->expectException(ErrorResponseException::class);
            $this->expectExceptionCode(404);
        }

        /* @var CombinationImporterInterface $importer */
        $importer = $this->createMock(CombinationImporterInterface::class);
        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);

        $handler = new CombinationPartHandler($importer, $modCombinationRepository, $registryService);
        $result = $this->invokeMethod($handler, 'fetchExportCombination', $combinationHash);

        $this->assertSame($combination, $result);
    }

    /**
     * Provides the data for the fetchDatabaseCombination test.
     * @return array
     */
    public function provideFetchDatabaseCombination(): array
    {
        $databaseCombination1 = new DatabaseCombination(new Mod('abc'), 'def');
        $databaseCombination2 = new DatabaseCombination(new Mod('ghi'), 'jkl');

        return [
            [[$databaseCombination1, $databaseCombination2], false, $databaseCombination1],
            [[], true, null]
        ];
    }

    /**
     * Tests the fetchDatabaseCombination method.
     * @param array $resultFind
     * @param bool $expectException
     * @param DatabaseCombination|null $expectedResult
     * @throws ReflectionException
     * @covers ::fetchDatabaseCombination
     * @dataProvider provideFetchDatabaseCombination
     */
    public function testFetchDatabaseCombination(
        array $resultFind,
        bool $expectException,
        ?DatabaseCombination $expectedResult
    ): void {
        $name = 'foo';
        $exportCombination = (new ExportCombination())->setName($name);

        /* @var ModCombinationRepository|MockObject $modCombinationRepository */
        $modCombinationRepository = $this->getMockBuilder(ModCombinationRepository::class)
                                         ->setMethods(['findByNames'])
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $modCombinationRepository->expects($this->once())
                                 ->method('findByNames')
                                 ->with([$name])
                                 ->willReturn($resultFind);

        if ($expectException) {
            $this->expectException(ErrorResponseException::class);
            $this->expectExceptionCode(400);
        }

        /* @var CombinationImporterInterface $importer */
        $importer = $this->createMock(CombinationImporterInterface::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $handler = new CombinationPartHandler($importer, $modCombinationRepository, $registryService);
        $result = $this->invokeMethod($handler, 'fetchDatabaseCombination', $exportCombination);

        $this->assertSame($expectedResult, $result);
    }
}
