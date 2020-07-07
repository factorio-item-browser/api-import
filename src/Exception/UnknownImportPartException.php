<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Exception;

use Throwable;

/**
 * The exception thrown when an unknown part of the import has been requested.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class UnknownImportPartException extends ImportException
{
    /**
     * The message template of the exception.
     */
    protected const MESSAGE = 'Unknown part to import: %s';

    /**
     * Initializes the exception.
     * @param string $name
     * @param Throwable|null $previous
     */
    public function __construct(string $name, ?Throwable $previous = null)
    {
        parent::__construct(sprintf(self::MESSAGE, $name), 500, $previous);
    }
}
