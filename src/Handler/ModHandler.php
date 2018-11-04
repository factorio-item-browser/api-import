<?php

namespace FactorioItemBrowser\Api\Import\Handler;

use FactorioItemBrowser\Api\Import\Exception\ErrorResponseException;
use FactorioItemBrowser\Api\Import\Exception\UnknownHashException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 *
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ModHandler implements RequestHandlerInterface
{
    /**
     * The registry service.
     * @var RegistryService
     */
    protected $registryService;

    /**
     * Initializes the handler.
     * @param RegistryService $registryService
     */
    public function __construct(RegistryService $registryService)
    {
        $this->registryService = $registryService;
    }

    /**
     * Handles a request and produces a response.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $modName = $request->getAttribute('modName');
        $exportMod = $this->fetchExportMod($modName);

        return new EmptyResponse();
    }

    protected function fetchExportMod(string $modName): ExportMod
    {
        try {
            $result = $this->registryService->getMod($modName);
        } catch (UnknownHashException $e) {
            throw new ErrorResponseException($e->getMessage(), 404, $e);
        }
        return $result;
    }
}
