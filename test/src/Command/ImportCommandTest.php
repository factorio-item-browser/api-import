<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
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
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

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
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
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

        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportDataService,
            $importers
        );

        $this->assertSame($this->combinationRepository, $this->extractProperty($command, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($command, 'console'));
        $this->assertSame($this->entityManager, $this->extractProperty($command, 'entityManager'));
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
        /* @var ImportCommand&MockObject $command */
        $command = $this->getMockBuilder(ImportCommand::class)
                        ->onlyMethods(['setName', 'setDescription', 'addArgument'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportDataService,
                            [],
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
     * Tests the getLabel method.
     * @throws ReflectionException
     * @covers ::getLabel
     */
    public function testGetLabel(): void
    {
        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportDataService,
            []
        );
        $result = $this->invokeMethod($command, 'getLabel');

        $this->assertSame('Processing the main data of the combination', $result);
    }

    /**
     * Tests the import method.
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        /* @var Combination&MockObject $combination */
        $combination = $this->createMock(Combination::class);

        /* @var ImporterInterface&MockObject $importer1 */
        $importer1 = $this->createMock(ImporterInterface::class);
        $importer1->expects($this->once())
                  ->method('prepare')
                  ->with($this->identicalTo($exportData));
        $importer1->expects($this->once())
                  ->method('parse')
                  ->with($this->identicalTo($exportData));
        $importer1->expects($this->once())
                  ->method('persist')
                  ->with($this->identicalTo($this->entityManager), $this->identicalTo($combination));
        $importer1->expects($this->once())
                  ->method('cleanup');

        /* @var ImporterInterface&MockObject $importer2 */
        $importer2 = $this->createMock(ImporterInterface::class);
        $importer2->expects($this->once())
                  ->method('prepare')
                  ->with($this->identicalTo($exportData));
        $importer2->expects($this->once())
                  ->method('parse')
                  ->with($this->identicalTo($exportData));
        $importer2->expects($this->once())
                  ->method('persist')
                  ->with($this->identicalTo($this->entityManager), $this->identicalTo($combination));
        $importer2->expects($this->once())
                  ->method('cleanup');

        $this->console->expects($this->exactly(4))
                      ->method('writeAction')
                      ->withConsecutive(
                          [$this->identicalTo('Preparing importers')],
                          [$this->identicalTo('Parsing the export data')],
                          [$this->identicalTo('Persisting the parsed data')],
                          [$this->identicalTo('Cleaning up obsolete data')]
                      );

        $command = new ImportCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportDataService,
            [$importer1, $importer2]
        );

        $this->invokeMethod($command, 'import', $exportData, $combination);
    }
}
