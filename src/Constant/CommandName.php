<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Constant;

/**
 * The interface holding the names of the commands.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
interface CommandName
{
    public const IMPORT = 'import';
    public const IMPORT_PART = 'import-part';

    public const IMPORT_IMAGES = 'import-images';
    public const IMPORT_TRANSLATIONS = 'import-translations';
    public const PROCESS = 'process';
}
