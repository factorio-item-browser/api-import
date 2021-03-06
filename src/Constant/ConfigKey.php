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
     * The main key for the config.
     */
    public const MAIN = 'api-import';

    /**
     * The chunk size to use for the imports.
     */
    public const IMPORT_CHUNK_SIZE = 'import-chunk-size';

    /**
     * The number of parallel processes to use on the import.
     */
    public const IMPORT_PARALLEL_PROCESSES = 'import-parallel-processes';

    /**
     * The key holding the importer aliases.
     */
    public const IMPORTERS = 'importers';
}
