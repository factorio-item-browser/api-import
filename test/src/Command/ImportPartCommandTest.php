<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Import\Command\ImportPartCommand;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\Api\Import\Exception\UnknownImportPartException;
use FactorioItemBrowser\Api\Import\Importer\ImporterInterface;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * The PHPUnit test of the ImportPartCommand class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Command\ImportPartCommand
 */
class ImportPartCommandTest extends TestCase
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

        $command = new ImportPartCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $importers,
        );

        $this->assertSame($this->combinationRepository, $this->extractProperty($command, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($command, 'console'));
        $this->assertSame($this->exportDataService, $this->extractProperty($command, 'exportDataService'));
        $this->assertSame($importers, $this->extractProperty($command, 'importers'));
    }

    /**
     * Tests the configure method.
     * @throws ReflectionException
     * @covers ::configure
     */
    public function testConfigure(): void
    {
        $command = $this->getMockBuilder(ImportPartCommand::class)
                        ->onlyMethods(['setName', 'setDescription', 'addArgument'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->exportDataService,
                            [],
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('setName')
                ->with($this->identicalTo(CommandName::IMPORT_PART));
        $command->expects($this->once())
                ->method('setDescription')
                ->with($this->isType('string'));
        $command->expects($this->exactly(4))
                ->method('addArgument')
                ->withConsecutive(
                    [
                        $this->identicalTo('combination'),
                        $this->identicalTo(InputArgument::REQUIRED),
                        $this->isType('string'),
                    ],
                    [
                        $this->identicalTo('part'),
                        $this->identicalTo(InputArgument::REQUIRED),
                        $this->isType('string'),
                    ],
                    [
                        $this->identicalTo('offset'),
                        $this->identicalTo(InputArgument::REQUIRED),
                        $this->isType('string'),
                    ],
                    [
                        $this->identicalTo('limit'),
                        $this->identicalTo(InputArgument::REQUIRED),
                        $this->isType('string'),
                    ],
                );

        $this->invokeMethod($command, 'configure');
    }

    /**
     * Tests the processInput method.
     * @throws ReflectionException
     * @covers ::processInput
     */
    public function testProcessInput(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(3))
              ->method('getArgument')
              ->withConsecutive(
                  [$this->identicalTo('part')],
                  [$this->identicalTo('offset')],
                  [$this->identicalTo('limit')],
              )
              ->willReturnOnConsecutiveCalls(
                  'def',
                  '1337',
                  '42',
              );

        $importer1 = $this->createMock(ImporterInterface::class);
        $importer2 = $this->createMock(ImporterInterface::class);
        $importer3 = $this->createMock(ImporterInterface::class);

        $importers = [
            'abc' => $importer1,
            'def' => $importer2,
            'ghi' => $importer3,
        ];

        $command = new ImportPartCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $importers,
        );
        $this->invokeMethod($command, 'processInput', $input);

        $this->assertSame($importer2, $this->extractProperty($command, 'importer'));
        $this->assertSame(1337, $this->extractProperty($command, 'offset'));
        $this->assertSame(42, $this->extractProperty($command, 'limit'));
    }

    /**
     * Tests the processInput method.
     * @throws ReflectionException
     * @covers ::processInput
     */
    public function testProcessInputWithUnknownPart(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
              ->method('getArgument')
              ->with($this->identicalTo('part'))
              ->willReturn('foo');

        $importers = [
            'abc' => $this->createMock(ImporterInterface::class),
            'def' => $this->createMock(ImporterInterface::class),
            'ghi' => $this->createMock(ImporterInterface::class),
        ];

        $this->expectException(UnknownImportPartException::class);

        $command = new ImportPartCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $importers,
        );
        $this->invokeMethod($command, 'processInput', $input);
    }

    /**
     * Tests the import method.
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        $combination = $this->createMock(Combination::class);
        $exportData = $this->createMock(ExportData::class);
        $offset = 1337;
        $limit = 42;

        $importer = $this->createMock(ImporterInterface::class);
        $importer->expects($this->once())
                 ->method('import')
                 ->with(
                     $this->identicalTo($combination),
                     $this->identicalTo($exportData),
                     $this->identicalTo($offset),
                     $this->identicalTo($limit),
                 );

        $command = new ImportPartCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            [],
        );
        $this->injectProperty($command, 'importer', $importer);
        $this->injectProperty($command, 'offset', $offset);
        $this->injectProperty($command, 'limit', $limit);

        $this->invokeMethod($command, 'import', $exportData, $combination);
    }
}
