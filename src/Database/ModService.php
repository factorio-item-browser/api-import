<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Database;

use FactorioItemBrowser\Api\Database\Entity\Mod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Exception\MissingEntityException;

/**
 * The service providing the mods.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ModService
{
    /**
     * The repository of the mods.
     * @var ModRepository
     */
    protected $modRepository;

    /**
     * The cache of the service.
     * @var array|Mod[]
     */
    protected $cache = [];

    /**
     * Initializes the service.
     * @param ModRepository $modRepository
     */
    public function __construct(ModRepository $modRepository)
    {
        $this->modRepository = $modRepository;
    }

    /**
     * Returns the mod with the specified name.
     * @param string $name
     * @return Mod
     * @throws MissingEntityException
     */
    public function getByName(string $name): Mod
    {
        if (!isset($this->cache[$name])) {
            $this->cache[$name] = $this->fetchByName($name);
        }
        return $this->cache[$name];
    }

    /**
     * Fetches the mod with the specified name.
     * @param string $name
     * @return Mod
     * @throws MissingEntityException
     */
    protected function fetchByName(string $name): Mod
    {
        $mods = $this->modRepository->findByNamesWithDependencies([$name]);
        $result = array_shift($mods);
        if (!$result instanceof Mod) {
            throw new MissingEntityException(Mod::class, $name);
        }
        return $result;
    }
}
