<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Exception;

use Throwable;

/**
 * The exception thrown when an expected item was not actually there.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class MissingItemException extends ImportException
{
    /**
     * The message template of the exception.
     */
    protected const MESSAGE = 'Expected item %s/%s, but it was not there.';

    /**
     * Initializes the exception.
     * @param string $type
     * @param string $name
     * @param Throwable|null $previous
     */
    public function __construct(string $type, string $name, ?Throwable $previous = null)
    {
        parent::__construct(sprintf(self::MESSAGE, $type, $name), 500, $previous);
    }
}
