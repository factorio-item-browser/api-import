<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Command\ImportCommand;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;

/**
 * The PHPUnit test of the ImportCommand class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Command\ImportCommand
 */
class ImportCommandTest extends TestCase
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
        $importers = [
            $this->createMock(ImporterInterface::class),
            $this->createMock(ImporterInterface::class),
        ];
        $chunkSize = 42;
        $numberOfParallelProcesses = 21;

        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $importers,
            $chunkSize,
            $numberOfParallelProcesses,
        );

        $this->assertSame($this->combinationRepository, $this->extractProperty($command, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($command, 'console'));
        $this->assertSame($this->exportDataService, $this->extractProperty($command, 'exportDataService'));
        $this->assertSame($importers, $this->extractProperty($command, 'importers'));
        $this->assertSame($chunkSize, $this->extractProperty($command, 'chunkSize'));
        $this->assertSame($numberOfParallelProcesses, $this->extractProperty($command, 'numberOfParallelProcesses'));
    }

    /**
     * Tests the configure method.
     * @throws ReflectionException
     * @covers ::configure
     */
    public function testConfigure(): void
    {
        $command = $this->getMockBuilder(ImportCommand::class)
                        ->onlyMethods(['setName', 'setDescription', 'addArgument'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->exportDataService,
                            [],
                            42,
                            21,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('setName')
                ->with($this->identicalTo(CommandName::IMPORT));
        $command->expects($this->once())
                ->method('setDescription')
                ->with($this->isType('string'));
        $command->expects($this->once())
                ->method('addArgument')
                ->with(
                    $this->identicalTo('combination'),
                    $this->identicalTo(InputArgument::REQUIRED),
                    $this->isType('string')
                );

        $this->invokeMethod($command, 'configure');
    }

    /**
     * Tests the import method.
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        $exportData = $this->createMock(ExportData::class);
        $combination = $this->createMock(Combination::class);

        $importer1 = $this->createMock(ImporterInterface::class);
        $importer2 = $this->createMock(ImporterInterface::class);

        $importers = [
            'abc' => $importer1,
            'def' => $importer2,
        ];

        $command = $this->getMockBuilder(ImportCommand::class)
                        ->onlyMethods(['executeImporter', 'cleanup'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->exportDataService,
                            $importers,
                            42,
                            21,
                        ])
                        ->getMock();
        $command->expects($this->exactly(2))
                ->method('executeImporter')
                ->withConsecutive(
                    [
                        $this->identicalTo('abc'),
                        $this->identicalTo($importer1),
                        $this->identicalTo($exportData),
                        $this->identicalTo($combination),
                    ],
                    [
                        $this->identicalTo('def'),
                        $this->identicalTo($importer2),
                        $this->identicalTo($exportData),
                        $this->identicalTo($combination),
                    ],
                );

        $this->invokeMethod($command, 'import', $exportData, $combination);
    }
}
