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
     * The key holding the API keys to access the importer.
     */
    public const API_KEYS = 'api-keys';

    /**
     * The key holding configuration values for the export data component.
     */
    public const EXPORT_DATA = 'export-data';

    /**
     * The key holding the directory of the export data.
     */
    public const EXPORT_DATA_DIRECTORY = 'directory';

    /**
     * The key holding the aliases for the repositories with orphans.
     */
    public const REPOSITORIES_WITH_ORPHANS = 'repositories-with-orphans';
}
