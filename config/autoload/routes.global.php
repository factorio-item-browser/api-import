<?php

declare(strict_types=1);

/**
 * The configuration of the routes.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace FactorioItemBrowser\Api\Import;

return [
    'routes' => [
        [
            'name' => 'import-images <combination>',
            'handler' => Command\ImportImagesCommand::class,
            'short_description' => 'Imports the images of a combination.',
        ],
        [
            'name' => 'import-translations <combination>',
            'handler' => Command\ImportTranslationsCommand::class,
            'short_description' => 'Imports the translations of a combination.',
        ],
        [
            'name' => 'process',
            'handler' => Command\ProcessCommand::class,
            'short_description' => 'Processes something',
        ],
    ]
];
