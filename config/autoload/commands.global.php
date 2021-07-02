<?php

/**
 * The file providing the commands.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import;

use FactorioItemBrowser\Api\Import\Constant\CommandName;

return [
    'commands' => [
        CommandName::CLEAN => Command\CleanCommand::class,
        CommandName::IMPORT => Command\ImportCommand::class,
        CommandName::IMPORT_PART => Command\ImportPartCommand::class,
        CommandName::PROCESS => Command\ProcessCommand::class,
    ],
];
