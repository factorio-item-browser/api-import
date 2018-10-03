<?php

namespace FactorioItemBrowser\Api\Import\Exception;

use Exception;

/**
 * The exception thrown when an entity cannot be found by its hash.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class UnknownHashException extends ImportException
{
    /**
     * Initializes the exception.
     * @param string $entityType
     * @param string $hash
     * @param Exception|null $previous
     */
    public function __construct(string $entityType, string $hash, ?Exception $previous = null)
    {
        parent::__construct('Unable to find ' . $entityType . ' with hash ' . $hash, 0, $previous);
    }
}
