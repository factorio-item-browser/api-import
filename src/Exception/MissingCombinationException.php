<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Exception;

use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * The exception thrown when an expected combination was not actually there.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class MissingCombinationException extends ImportException
{
    /**
     * The message of the exception.
     */
    protected const MESSAGE = 'Expected combination %s, but it was not there.';

    /**
     * Initializes the exception.
     * @param UuidInterface $combinationId
     * @param Throwable|null $previous
     */
    public function __construct(UuidInterface $combinationId, ?Throwable $previous = null)
    {
        parent::__construct(sprintf(self::MESSAGE, $combinationId->toString()), 0, $previous);
    }
}
