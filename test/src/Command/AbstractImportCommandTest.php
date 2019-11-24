<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Command\AbstractImportCommand;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Exception\MissingCombinationException;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * The PHPUnit test of the AbstractImportCommand class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Command\AbstractImportCommand
 */
class AbstractImportCommandTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked combination repository.
     * @var CombinationRepository&MockObject
     */
    protected $combinationRepository;

    /**
     * The mocked console.
     * @var Console&MockObject
     */
    protected $console;

    /**
     * The mocked export data service.
     * @var ExportDataService&MockObject
     */
    protected $exportDataService;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->combinationRepository = $this->createMock(CombinationRepository::class);
        $this->console = $this->createMock(Console::class);
        $this->exportDataService = $this->createMock(ExportDataService::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var AbstractImportCommand&MockObject $command */
        $command = $this->getMockBuilder(AbstractImportCommand::class)
                        ->setConstructorArgs([$this->combinationRepository, $this->console, $this->exportDataService])
                        ->getMockForAbstractClass();

        $this->assertSame($this->combinationRepository, $this->extractProperty($command, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($command, 'console'));
        $this->assertSame($this->exportDataService, $this->extractProperty($command, 'exportDataService'));
    }

    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        $combinationIdString = '70acdb0f-36ca-4b30-9687-2baaade94cd3';
        $expectedCombinationId = Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        $label = 'abc';
        $expectedResult = 0;

        /* @var AdapterInterface&MockObject $consoleAdapter */
        $consoleAdapter = $this->createMock(AdapterInterface::class);
        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        /* @var Combination&MockObject $combination */
        $combination = $this->createMock(Combination::class);

        /* @var Route&MockObject $route */
        $route = $this->createMock(Route::class);
        $route->expects($this->once())
              ->method('getMatchedParam')
              ->with($this->identicalTo('combination'))
              ->willReturn($combinationIdString);

        $this->console->expects($this->once())
                      ->method('writeStep')
                      ->with($this->identicalTo($label));
        $this->console->expects($this->never())
                      ->method('writeException');

        $this->exportDataService->expects($this->once())
                                ->method('loadExport')
                                ->with($this->identicalTo($combinationIdString))
                                ->willReturn($exportData);

        $this->combinationRepository->expects($this->once())
                                    ->method('findById')
                                    ->with($this->equalTo($expectedCombinationId))
                                    ->willReturn($combination);

        /* @var AbstractImportCommand&MockObject $command */
        $command = $this->getMockBuilder(AbstractImportCommand::class)
                        ->onlyMethods(['getLabel', 'import'])
                        ->setConstructorArgs([$this->combinationRepository, $this->console, $this->exportDataService])
                        ->getMockForAbstractClass();
        $command->expects($this->once())
                ->method('getLabel')
                ->willReturn($label);
        $command->expects($this->once())
                ->method('import')
                ->with($this->identicalTo($exportData), $this->identicalTo($combination));

        $result = $command($route, $consoleAdapter);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the invoking.
     * @throws ReflectionException
     * @covers ::__invoke
     */
    public function testInvokeWithoutCombination(): void
    {
        $combinationIdString = '70acdb0f-36ca-4b30-9687-2baaade94cd3';
        $expectedCombinationId = Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        $label = 'abc';
        $expectedResult = 1;

        /* @var AdapterInterface&MockObject $consoleAdapter */
        $consoleAdapter = $this->createMock(AdapterInterface::class);
        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);

        /* @var Route&MockObject $route */
        $route = $this->createMock(Route::class);
        $route->expects($this->once())
              ->method('getMatchedParam')
              ->with($this->identicalTo('combination'))
              ->willReturn($combinationIdString);

        $this->console->expects($this->once())
                      ->method('writeStep')
                      ->with($this->identicalTo($label));
        $this->console->expects($this->once())
                      ->method('writeException')
                      ->with($this->isInstanceOf(MissingCombinationException::class));

        $this->exportDataService->expects($this->once())
                                ->method('loadExport')
                                ->with($this->identicalTo($combinationIdString))
                                ->willReturn($exportData);

        $this->combinationRepository->expects($this->once())
                                    ->method('findById')
                                    ->with($this->equalTo($expectedCombinationId))
                                    ->willReturn(null);

        /* @var AbstractImportCommand&MockObject $command */
        $command = $this->getMockBuilder(AbstractImportCommand::class)
                        ->onlyMethods(['getLabel', 'import'])
                        ->setConstructorArgs([$this->combinationRepository, $this->console, $this->exportDataService])
                        ->getMockForAbstractClass();
        $command->expects($this->once())
                ->method('getLabel')
                ->willReturn($label);
        $command->expects($this->never())
                ->method('import');

        $result = $command($route, $consoleAdapter);
        $this->assertSame($expectedResult, $result);
    }
}
