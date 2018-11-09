<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Middleware;

use FactorioItemBrowser\Api\Import\Exception\ErrorResponseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The middleware for checking the API key.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ApiKeyMiddleware implements MiddlewareInterface
{
    /**
     * The API keys.
     * @var array
     */
    protected $apiKeys;

    /**
     * Initializes the middleware.
     * @param array $apiKeys
     */
    public function __construct(array $apiKeys)
    {
        $this->apiKeys = $apiKeys;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating response creation to a handler.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ErrorResponseException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationKey = $request->getHeaderLine('X-Api-Key');
        if ($authorizationKey === '' || array_search($authorizationKey, $this->apiKeys, true) === false) {
            throw new ErrorResponseException('API key is missing or invalid.', 401);
        }

        return $handler->handle($request);
    }
}
