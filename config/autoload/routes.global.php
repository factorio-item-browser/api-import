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
            'name' => 'import <combination>',
            'handler' => Command\ImportCommand::class,
            'short_description' => 'Imports the main data of a combination.',
            'options_description' => [
                '<combination>' => 'The id of the combination to import.'
            ]
        ],
        [
            'name' => 'import-images <combination>',
            'handler' => Command\ImportImagesCommand::class,
            'short_description' => 'Imports the images of a combination.',
            'options_description' => [
                '<combination>' => 'The id of the combination to import.'
            ]
        ],
        [
            'name' => 'import-translations <combination>',
            'handler' => Command\ImportTranslationsCommand::class,
            'short_description' => 'Imports the translations of a combination.',
            'options_description' => [
                '<combination>' => 'The id of the combination to import.'
            ]
        ],


        [
            'name' => 'process',
            'handler' => Command\ProcessCommand::class,
            'short_description' => 'Processes something',
        ],
    ]
];
