<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Process;

use FactorioItemBrowser\Api\Database\Entity\Combination;
use FactorioItemBrowser\Api\Import\Process\ImportCommandProcess;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * The PHPUnit test of the ImportCommandProcess class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Process\ImportCommandProcess
 */
class ImportCommandProcessTest extends TestCase
{
    /**
     * Tests the constructing.
     * @covers ::__construct
     * @covers ::getCombination
     */
    public function testConstruct(): void
    {
        $commandName = 'abc';

        $combination = new Combination();
        $combination->setId(Uuid::fromString('4ab1d86a-0151-4420-aca1-a491e8f44703'));

        $command = $_SERVER['_'] ?? 'php';
        $expectedCommandLine
            = "'{$command}' '{$_SERVER['SCRIPT_FILENAME']}' 'abc' '4ab1d86a-0151-4420-aca1-a491e8f44703'";

        $process = new ImportCommandProcess($commandName, $combination);

        $this->assertSame($combination, $process->getCombination());
        $this->assertNull($process->getTimeout());
        $this->assertSame($expectedCommandLine, $process->getCommandLine());
    }
}
