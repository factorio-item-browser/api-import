<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Exception;

use Throwable;

/**
 * The exception thrown when an expected entity is missing.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class MissingEntityException extends ImportException
{
    /**
     * Initializes the exception.
     * @param string $entityClass
     * @param string $identifier
     * @param Throwable|null $previous
     */
    public function __construct(string $entityClass, string $identifier, ?Throwable $previous = null)
    {
        $entityName = basename(str_replace('\\', '/', $entityClass));

        parent::__construct('Missing ' . $entityName . ': ' . $identifier, 0, $previous);
    }
}
