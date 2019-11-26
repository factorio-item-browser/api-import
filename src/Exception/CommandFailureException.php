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
    protected const PREFIX = "\e[1;31m! ";

    /**
     * The suffix of the actual error message.
     */
    protected const SUFFIX = "\x1b[22;39m\x1b[0;49m";

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
        foreach (explode(PHP_EOL, $commandOutput) as $line) {
            if (substr($line, 0, strlen(self::PREFIX)) === self::PREFIX) {
                $message = substr($line, strlen(self::PREFIX));
                if (substr($message, -strlen(self::SUFFIX)) === self::SUFFIX) {
                    $message = substr($message, 0, -strlen(self::SUFFIX));
                }
                return $message;
            }
        }
        return '';
    }
}
