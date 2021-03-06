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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $command = $this->getMockBuilder(AbstractImportCommand::class)
                        ->setConstructorArgs([$this->combinationRepository, $this->console, $this->exportDataService])
                        ->getMockForAbstractClass();

        $this->assertSame($this->combinationRepository, $this->extractProperty($command, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($command, 'console'));
        $this->assertSame($this->exportDataService, $this->extractProperty($command, 'exportDataService'));
    }

    /**
     * Tests the configure method.
     * @throws ReflectionException
     * @covers ::configure
     */
    public function testConfigure(): void
    {
        $command = $this->getMockBuilder(AbstractImportCommand::class)
                        ->onlyMethods(['addArgument'])
                        ->setConstructorArgs([$this->combinationRepository, $this->console, $this->exportDataService])
                        ->getMockForAbstractClass();
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
     * Tests the execute method.
     * @throws ReflectionException
     * @covers ::execute
     */
    public function testExecute(): void
    {
        $combinationIdString = '70acdb0f-36ca-4b30-9687-2baaade94cd3';
        $expectedCombinationId = Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        $expectedResult = 0;

        $output = $this->createMock(OutputInterface::class);
        $exportData = $this->createMock(ExportData::class);
        $combination = $this->createMock(Combination::class);

        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
              ->method('getArgument')
              ->with($this->identicalTo('combination'))
              ->willReturn($combinationIdString);

        $this->exportDataService->expects($this->once())
                                ->method('loadExport')
                                ->with($this->identicalTo($combinationIdString))
                                ->willReturn($exportData);

        $this->combinationRepository->expects($this->once())
                                    ->method('findById')
                                    ->with($this->equalTo($expectedCombinationId))
                                    ->willReturn($combination);

        $command = $this->getMockBuilder(AbstractImportCommand::class)
                        ->onlyMethods(['import'])
                        ->setConstructorArgs([$this->combinationRepository, $this->console, $this->exportDataService])
                        ->getMockForAbstractClass();
        $command->expects($this->once())
                ->method('import')
                ->with($this->identicalTo($exportData), $this->identicalTo($combination));

        $result = $this->invokeMethod($command, 'execute', $input, $output);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the execute method.
     * @throws ReflectionException
     * @covers ::execute
     */
    public function testExecuteWithoutCombination(): void
    {
        $combinationIdString = '70acdb0f-36ca-4b30-9687-2baaade94cd3';
        $expectedCombinationId = Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        $expectedResult = 1;

        $output = $this->createMock(OutputInterface::class);
        $exportData = $this->createMock(ExportData::class);

        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
              ->method('getArgument')
              ->with($this->identicalTo('combination'))
              ->willReturn($combinationIdString);

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

        $command = $this->getMockBuilder(AbstractImportCommand::class)
                        ->onlyMethods(['import'])
                        ->setConstructorArgs([$this->combinationRepository, $this->console, $this->exportDataService])
                        ->getMockForAbstractClass();
        $command->expects($this->never())
                ->method('import');

        $result = $this->invokeMethod($command, 'execute', $input, $output);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the processInput method.
     * @throws ReflectionException
     * @covers ::processInput
     */
    public function testProcessInput(): void
    {
        $input = $this->createMock(InputInterface::class);

        $command = $this->getMockBuilder(AbstractImportCommand::class)
                        ->onlyMethods(['import'])
                        ->setConstructorArgs([$this->combinationRepository, $this->console, $this->exportDataService])
                        ->getMockForAbstractClass();
        $this->invokeMethod($command, 'processInput', $input);

        $this->addToAssertionCount(1);
    }
}
