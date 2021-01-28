<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Console;

use Exception;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * The wrapper class for the actual console.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class Console
{
    protected OutputInterface $output;
    protected bool $isDebug;
    protected Terminal $terminal;

    public function __construct(OutputInterface $output, bool $isDebug)
    {
        $this->output = $output;
        $this->isDebug = $isDebug;
        $this->terminal = new Terminal();
    }

    /**
     * Writes a headline with the specified message.
     * @param string $message
     * @return $this
     */
    public function writeHeadline(string $message): self
    {
        $this->output->writeln(<<<EOT
            
            <bg=cyan;fg=black;options=bold>{$this->createHorizontalLine(' ')}
             {$message}
            </>
            EOT);
        return $this;
    }

    /**
     * Writes a step to the console.
     * @param string $step
     * @return $this
     */
    public function writeStep(string $step): self
    {
        $this->output->writeln(<<<EOT
            
            <fg=cyan;options=bold> {$step}
            {$this->createHorizontalLine('-')}</>
            EOT);
        return $this;
    }

    /**
     * Writes an action to the console.
     * @param string $action
     * @return $this
     */
    public function writeAction(string $action): self
    {
        $this->output->writeln('> ' . $action . '...');
        return $this;
    }

    /**
     * Writes a simple message, like a comment, to the console.
     * @param string $message
     * @return $this
     */
    public function writeMessage(string $message): self
    {
        $this->output->writeln('# ' . $message);
        return $this;
    }

    /**
     * Writes an exception to the console.
     * @param Exception $e
     * @return $this
     */
    public function writeException(Exception $e): self
    {
        $exceptionName = substr((string) strrchr(get_class($e), '\\'), 1);
        $this->output->writeln(<<<EOT
            
            <bg=red;fg=white;options=bold>{$this->createHorizontalLine(' ')}
             {$exceptionName}: {$e->getMessage()}
            </>
            EOT);

        if ($this->isDebug) {
            $this->output->writeln("<fg=red>{$e->getTraceAsString()}</>");
        }
        return $this;
    }

    /**
     * Writes raw data to the console.
     * @param string $data
     * @return $this
     */
    public function writeData(string $data): self
    {
        $this->output->write($data, false, ConsoleOutput::OUTPUT_RAW);
        return $this;
    }

    protected function createHorizontalLine(string $character): string
    {
        return str_pad('', $this->terminal->getWidth(), $character);
    }
}
