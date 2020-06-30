<?php

declare(strict_types=1);

/**
 * The file providing the commands.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\Api\Import\Constant\CommandName;

return [
    'commands' => [
        CommandName::IMPORT => Command\ImportCommand::class,
        CommandName::IMPORT_PART => Command\ImportPartCommand::class,
        CommandName::IMPORT_IMAGES => Command\ImportImagesCommand::class,
        CommandName::IMPORT_TRANSLATIONS => Command\ImportTranslationsCommand::class,
        CommandName::PROCESS => Command\ProcessCommand::class,
    ],
];
