<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Console;

use Exception;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The wrapper class for the actual console.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class Console
{
    /**
     * The output instance.
     * @var OutputInterface
     */
    protected $output;

    /**
     * Whether the debug mode is enabled.
     * @var bool
     */
    protected $isDebug;

    /**
     * Initializes the console wrapper.
     * @param OutputInterface $output
     * @param bool $isDebug
     */
    public function __construct(OutputInterface $output, bool $isDebug)
    {
        $this->output = $output;
        $this->isDebug = $isDebug;
    }

    /**
     * Writes a headline with the specified message.
     * @param string $message
     * @return $this
     */
    public function writeHeadline(string $message): self
    {
        $this->writeWithDecoration([
            '',
            $this->createHorizontalLine('-'),
            ' ' . $message,
            $this->createHorizontalLine('-'),
        ], 'yellow', 'bold');
        return $this;
    }

    /**
     * Writes a step to the console.
     * @param string $step
     * @return $this
     */
    public function writeStep(string $step): self
    {
        $this->writeWithDecoration([
            '',
            ' ' . $step,
            $this->createHorizontalLine('-')
        ], 'blue', 'bold');
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
        $this->writeWithDecoration([
            sprintf('! %s: %s', substr((string) strrchr(get_class($e), '\\'), 1), $e->getMessage()),
        ], 'red', 'bold');

        if ($this->isDebug) {
            $this->writeWithDecoration([
                $this->createHorizontalLine('-'),
                $e->getTraceAsString(),
                $this->createHorizontalLine('-'),
            ], 'red');
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

    /**
     * Writes messages with decorations.
     * @param array|string[] $messages
     * @param string $color
     * @param string $options
     */
    protected function writeWithDecoration(array $messages, string $color = '', string $options = ''): void
    {
        $messages = array_values(array_map([OutputFormatter::class, 'escape'], $messages));

        $formats = [];
        if ($color !== '') {
            $formats[] = "fg={$color}";
        }
        if ($options !== '') {
            $formats[] = "options={$options}";
        }
        $formatString = implode(';', $formats);
        if ($formatString !== '') {
            $messages[0] = "<{$formatString}>{$messages[0]}";
            $messages[count($messages) - 1] .= '</>';
        }

        $this->output->writeln($messages);
    }

    /**
     * Creates a horizontal line of the specified character.
     * @param string $character
     * @return string
     */
    protected function createHorizontalLine(string $character): string
    {
        return str_pad('', 80, $character);
    }
}
