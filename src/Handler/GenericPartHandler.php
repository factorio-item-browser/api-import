<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Handler;

use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\Generic\GenericImporterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * The handler for importing a generic part of data.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class GenericPartHandler implements RequestHandlerInterface
{
    /**
     * The importer.
     * @var GenericImporterInterface
     */
    protected $importer;

    /**
     * Initializes the handler.
     * @param GenericImporterInterface $importer
     */
    public function __construct(GenericImporterInterface $importer)
    {
        $this->importer = $importer;
    }

    /**
     * Handle the request and return a response.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ImportException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->importer->import();

        return new EmptyResponse();
    }
}
