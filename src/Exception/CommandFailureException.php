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
    private const START_LINE = "\e[37;41;1m";
    private const END_LINE = "\e[39;49;22m";

    public function __construct(string $commandOutput, ?Throwable $previous = null)
    {
        parent::__construct($this->extractExceptionMessage($commandOutput), 0, $previous);
    }

    protected function extractExceptionMessage(string $commandOutput): string
    {
        $catchingMessage = false;
        $lines = [];
        foreach (explode(PHP_EOL, $commandOutput) as $line) {
            $line = trim($line);
            if ($line === self::START_LINE) {
                $catchingMessage = true;
            } elseif ($line === self::END_LINE) {
                break;
            } elseif ($catchingMessage) {
                $lines[] = $line;
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
