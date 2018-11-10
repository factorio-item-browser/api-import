<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Importer\Generic;

use FactorioItemBrowser\Api\Database\Repository\CachedSearchResultRepository;

/**
 * The importer actually clearing database caches.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ClearCacheImporter implements GenericImporterInterface
{
    /**
     * The repository of the cached results.
     * @var CachedSearchResultRepository
     */
    protected $cachedSearchResultRepository;

    /**
     * Initializes the importer.
     * @param CachedSearchResultRepository $cachedSearchResultRepository
     */
    public function __construct(CachedSearchResultRepository $cachedSearchResultRepository)
    {
        $this->cachedSearchResultRepository = $cachedSearchResultRepository;
    }

    /**
     * Imports some generic data.
     */
    public function import(): void
    {
        $this->cachedSearchResultRepository->clear();
    }
}
