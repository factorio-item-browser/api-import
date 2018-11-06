<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Response;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use Zend\Log\LoggerInterface;

/**
 * The class generating the error response.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ErrorResponseGenerator
{
    /**
     * The logger.
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Initializes the generator.
     * @param LoggerInterface|null $logger
     */
    public function __construct(?LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handles the thrown exception.
     * @param Throwable $exception
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function __invoke(
        Throwable $exception,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $statusCode = $exception->getCode();
        if ($statusCode < 400 || $statusCode >= 600) {
            $statusCode = 500;
        }
        if ($statusCode >= 500 && $this->logger instanceof LoggerInterface) {
            $this->logger->crit($exception);
        }

        return new Response($this->createResponseStream($exception->getMessage()), $statusCode);
    }

    /**
     * Creates the stream to use in the response.
     * @param string $content
     * @return StreamInterface
     */
    protected function createResponseStream(string $content): StreamInterface
    {
        $result = new Stream('php://memory', 'w');
        $result->write($content);
        $result->rewind();
        return $result;
    }
}
