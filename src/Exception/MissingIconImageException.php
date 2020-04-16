<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Exception;

use Throwable;

/**
 * The exception thrown when an expected icon image was not there.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class MissingIconImageException extends ImportException
{
    /**
     * The message template of the exception.
     */
    protected const MESSAGE = 'Expected icon image %s, but it was not there.';

    /**
     * Initializes the exception.
     * @param string $imageId
     * @param Throwable|null $previous
     */
    public function __construct(string $imageId, ?Throwable $previous = null)
    {
        parent::__construct(sprintf(self::MESSAGE, $imageId), 500, $previous);
    }
}
