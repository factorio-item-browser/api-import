<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Handler;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Exception\ErrorResponseException;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Handler\ModHandler;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * The PHPUnit test of the ModHandler class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Handler\ModHandler
 */
class ModHandlerTest extends TestCase
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

        $handler = new ModHandler($entityManager, $modRepository, $registryService);

        $this->assertSame($entityManager, $this->extractProperty($handler, 'entityManager'));
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

        /* @var ModHandler|MockObject $handler */
        $handler = $this->getMockBuilder(ModHandler::class)
                        ->setMethods(['fetchExportMod', 'fetchDatabaseMod', 'mapMetaData', 'flushEntities'])
                        ->disableOriginalConstructor()
                        ->getMock();
        $handler->expects($this->once())
                ->method('fetchExportMod')
                ->with($modName)
                ->willReturn($exportMod);
        $handler->expects($this->once())
                ->method('fetchDatabaseMod')
                ->with($exportMod)
                ->willReturn($databaseMod);
        $handler->expects($this->once())
                ->method('mapMetaData')
                ->with($exportMod, $databaseMod);
        $handler->expects($this->once())
                ->method('flushEntities');

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
        $exportMod = (new ExportMod())->setName('def');

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
                            ->willReturn($exportMod);
        }

        if ($expectException) {
            $this->expectException(ErrorResponseException::class);
            $this->expectExceptionCode(404);
        }

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        $handler = new ModHandler($entityManager, $modRepository, $registryService);
        $result = $this->invokeMethod($handler, 'fetchExportMod', $modName);

        $this->assertSame($exportMod, $result);
    }

    /**
     * Provides the data for the fetchDatabaseMod test.
     * @return array
     */
    public function provideFetchDatabaseMod(): array
    {
        $databaseMod = new DatabaseMod('foo');

        return [
            [[$databaseMod], null, $databaseMod],
            [[], $databaseMod, $databaseMod],
        ];
    }

    /**
     * Tests the fetchDatabaseMod method.
     * @param array|DatabaseMod[] $resultFind
     * @param DatabaseMod|null $resultCreate
     * @param DatabaseMod|null $expectedResult
     * @throws ReflectionException
     * @covers ::fetchDatabaseMod
     * @dataProvider provideFetchDatabaseMod
     */
    public function testFetchDatabaseMod(
        array $resultFind,
        ?DatabaseMod $resultCreate,
        ?DatabaseMod $expectedResult
    ): void {
        $modName = 'abc';
        $exportMod = (new ExportMod())->setName($modName);

        /* @var ModRepository|MockObject $modRepository */
        $modRepository = $this->getMockBuilder(ModRepository::class)
                              ->setMethods(['findByNamesWithDependencies'])
                              ->disableOriginalConstructor()
                              ->getMock();
        $modRepository->expects($this->once())
                      ->method('findByNamesWithDependencies')
                      ->with([$modName])
                      ->willReturn($resultFind);

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        /* @var ModHandler|MockObject $handler */
        $handler = $this->getMockBuilder(ModHandler::class)
                        ->setMethods(['createDatabaseMod'])
                        ->setConstructorArgs([$entityManager, $modRepository, $registryService])
                        ->getMock();
        $handler->expects($resultCreate === null ? $this->never() : $this->once())
                ->method('createDatabaseMod')
                ->with($exportMod)
                ->willReturn($resultCreate);

        $result = $this->invokeMethod($handler, 'fetchDatabaseMod', $exportMod);
        $this->assertSame($expectedResult, $result);
    }

        /**
     * Provides the data for the createDatabaseMod test.
     * @return array
     */
    public function provideCreateDatabaseMod(): array
    {
        return [
            [false, false],
            [true, true],
        ];
    }

    /**
     * Tests the createDatabaseMod method.
     * @param bool $throwException
     * @param bool $expectException
     * @throws ReflectionException
     * @covers ::createDatabaseMod
     * @dataProvider provideCreateDatabaseMod
     */
    public function testCreateDatabaseMod(bool $throwException, bool $expectException): void
    {
        $modName = 'abc';
        $exportMod = (new ExportMod())->setName($modName);
        $expectedResult = new DatabaseMod($modName);

        /* @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManagerInterface::class)
                              ->setMethods(['persist'])
                              ->getMockForAbstractClass();
        if ($throwException) {
            $entityManager->expects($this->once())
                          ->method('persist')
                          ->with($this->isInstanceOf(DatabaseMod::class))
                          ->willThrowException($this->createMock(ORMException::class));
        } else {
            $entityManager->expects($this->once())
                          ->method('persist')
                          ->with($this->isInstanceOf(DatabaseMod::class));
        }

        if ($expectException) {
            $this->expectException(ErrorResponseException::class);
            $this->expectExceptionCode(500);
        }

        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $handler = new ModHandler($entityManager, $modRepository, $registryService);
        $result = $this->invokeMethod($handler, 'createDatabaseMod', $exportMod);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the mapMetaData method.
     * @throws ReflectionException
     * @covers ::mapMetaData
     */
    public function testMapMetaData(): void
    {
        $author = 'abc';
        $version = '1.2.3';

        $exportMod = new ExportMod();
        $exportMod->setAuthor($author)
                  ->setVersion($version);

        /* @var DatabaseMod|MockObject $databaseMod */
        $databaseMod = $this->getMockBuilder(DatabaseMod::class)
                            ->setMethods(['setAuthor', 'setCurrentVersion'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $databaseMod->expects($this->once())
                    ->method('setAuthor')
                    ->with($author)
                    ->willReturnSelf();
        $databaseMod->expects($this->once())
                    ->method('setCurrentVersion')
                    ->with($version)
                    ->willReturnSelf();

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $handler = new ModHandler($entityManager, $modRepository, $registryService);
        $this->invokeMethod($handler, 'mapMetaData', $exportMod, $databaseMod);
    }

    /**
     * Provides the data for the flushEntities test.
     * @return array
     */
    public function provideFlushEntities(): array
    {
        return [
            [false, false],
            [true, true],
        ];
    }

    /**
     * Tests the flushEntities method.
     * @param bool $throwException
     * @param bool $expectException
     * @throws ReflectionException
     * @covers ::flushEntities
     * @dataProvider provideFlushEntities
     */
    public function testFlushEntities(bool $throwException, bool $expectException): void
    {
        /* @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManagerInterface::class)
                              ->setMethods(['flush'])
                              ->getMockForAbstractClass();
        if ($throwException) {
            $entityManager->expects($this->once())
                          ->method('flush')
                          ->willThrowException($this->createMock(ORMException::class));
        } else {
            $entityManager->expects($this->once())
                          ->method('flush');
        }

        if ($expectException) {
            $this->expectException(ErrorResponseException::class);
            $this->expectExceptionCode(500);
        }

        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $handler = new ModHandler($entityManager, $modRepository, $registryService);
        $this->invokeMethod($handler, 'flushEntities');
    }
}
