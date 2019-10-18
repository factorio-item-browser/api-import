<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Constant;

/**
 * The interface holding the config keys.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface ConfigKey
{
    /**
     * The key holding the name of the project.
     */
    public const PROJECT = 'factorio-item-browser';

    /**
     * The key holding the name of the API server itself.
     */
    public const API_IMPORT = 'api-import';

    /**
     * The key holding the importer aliases.
     */
    public const IMPORTERS = 'importers';
}
