<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Console;

use Exception;
use Zend\Console\Adapter\AdapterInterface;
use Zend\Console\ColorInterface;

/**
 * The wrapper class for the actual console.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class Console
{
    /**
     * The actual console instance.
     * @var AdapterInterface
     */
    protected $consoleAdapter;

    /**
     * Whether the debug mode is enabled.
     * @var bool
     */
    protected $isDebug;

    /**
     * Initializes the console wrapper.
     * @param AdapterInterface $consoleAdapter
     * @param bool $isDebug
     */
    public function __construct(AdapterInterface $consoleAdapter, bool $isDebug)
    {
        $this->consoleAdapter = $consoleAdapter;
        $this->isDebug = $isDebug;
    }

    /**
     * Writes a headline with the specified message.
     * @param string $message
     * @return $this
     */
    public function writeHeadline(string $message): self
    {
        $this->consoleAdapter->writeLine();
        $this->consoleAdapter->writeLine($this->createHorizontalLine('-'), ColorInterface::LIGHT_YELLOW);
        $this->consoleAdapter->writeLine(' ' . $message, ColorInterface::LIGHT_YELLOW);
        $this->consoleAdapter->writeLine($this->createHorizontalLine('-'), ColorInterface::LIGHT_YELLOW);
        return $this;
    }

    /**
     * Writes a step to the console.
     * @param string $step
     * @return $this
     */
    public function writeStep(string $step): self
    {
        $this->consoleAdapter->writeLine();
        $this->consoleAdapter->writeLine(' ' . $step, ColorInterface::LIGHT_BLUE);
        $this->consoleAdapter->writeLine($this->createHorizontalLine('-'), ColorInterface::LIGHT_BLUE);
        return $this;
    }

    /**
     * Writes an action to the console.
     * @param string $action
     * @return $this
     */
    public function writeAction(string $action): self
    {
        $this->consoleAdapter->writeLine('> ' . $action . '...');
        return $this;
    }

    /**
     * Writes a simple message, like a comment, to the console.
     * @param string $message
     * @return $this
     */
    public function writeMessage(string $message): self
    {
        $this->consoleAdapter->writeLine('# ' . $message);
        return $this;
    }

    /**
     * Writes an exception to the console.
     * @param Exception $e
     * @return $this
     */
    public function writeException(Exception $e): self
    {
        $this->consoleAdapter->writeLine(
            sprintf('! %s: %s', substr((string) strrchr(get_class($e), '\\'), 1), $e->getMessage()),
            ColorInterface::LIGHT_RED
        );

        if ($this->isDebug) {
            $this->consoleAdapter->writeLine($this->createHorizontalLine('-'), ColorInterface::RED);
            $this->consoleAdapter->writeLine($e->getTraceAsString(), ColorInterface::RED);
            $this->consoleAdapter->writeLine($this->createHorizontalLine('-'), ColorInterface::RED);
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
        $this->consoleAdapter->write($data);
        return $this;
    }

    /**
     * Creates a horizontal line of the specified character.
     * @param string $character
     * @return string
     */
    protected function createHorizontalLine(string $character): string
    {
        return str_pad('', $this->consoleAdapter->getWidth(), $character);
    }
}
