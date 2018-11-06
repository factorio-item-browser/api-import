<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Response;

use FactorioItemBrowser\Api\Import\Constant\ServiceName;
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
     * Creates the error response generator.
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return ErrorResponseGenerator
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $logger = null;
        if ($container->has(ServiceName::LOGGER)) {
            /* @var LoggerInterface $logger */
            $logger = $container->get(ServiceName::LOGGER);
        }

        return new ErrorResponseGenerator($logger);
    }
}
