<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Response;

use Interop\Container\ContainerInterface;
use Zend\Log\LoggerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * The factory of the error response generator class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ErrorResponseGeneratorFactory implements FactoryInterface
{
    /**
     * The name of the logger service.
     */
    public const LOGGER_SERVICE_NAME = 'logger.factorio-item-browser';

    /**
     * Creates the error response generator.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ErrorResponseGenerator
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $logger = null;
        if ($container->has(self::LOGGER_SERVICE_NAME)) {
            /* @var LoggerInterface $logger */
            $logger = $container->get(self::LOGGER_SERVICE_NAME);
        }

        return new ErrorResponseGenerator($logger);
    }
}
