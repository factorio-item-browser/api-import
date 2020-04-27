<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Exception;

use Throwable;

/**
 * The exception thrown when a command fails.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class CommandFailureException extends ImportException
{
    /**
     * The prefix of the actual error message.
     */
    protected const PREFIX = "\e[31;1m! ";

    /**
     * The suffix of the actual error message.
     */
    protected const SUFFIX = "\e[39;22m";

    /**
     * Initializes the exception.
     * @param string $commandOutput
     * @param Throwable|null $previous
     */
    public function __construct(string $commandOutput, ?Throwable $previous = null)
    {
        parent::__construct($this->extractExceptionMessage($commandOutput), 0, $previous);
    }

    /**
     * Extracts the actual exception message from the command output.
     * @param string $commandOutput
     * @return string
     */
    protected function extractExceptionMessage(string $commandOutput): string
    {
        $catchingMessage = false;
        $lines = [];
        foreach (explode(PHP_EOL, $commandOutput) as $line) {
            if (substr($line, 0, strlen(self::PREFIX)) === self::PREFIX) {
                $line = substr($line, strlen(self::PREFIX));
                $catchingMessage = true;
            }

            if ($catchingMessage) {
                if (substr($line, -strlen(self::SUFFIX)) === self::SUFFIX) {
                    $line = substr($line, 0, -strlen(self::SUFFIX));
                    $lines[] = $line;
                    break;
                }
                $lines[] = $line;
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
