<?php

declare(strict_types=1);

namespace FactorioItemBrowser\Api\Import\Process;

use FactorioItemBrowser\Api\Database\Entity\Combination;
use Symfony\Component\Process\Process;

/**
 * The process starting an import command.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 */
class ImportCommandProcess extends Process
{
    protected Combination $combination;

    /**
     * @param string $commandName
     * @param Combination $combination
     * @param array<string> $additionalAgruments
     */
    public function __construct(string $commandName, Combination $combination, array $additionalAgruments = [])
    {
        $this->combination = $combination;

        parent::__construct([
            $_SERVER['_'] ?? 'php',
            $_SERVER['SCRIPT_FILENAME'],
            $commandName,
            $combination->getId()->toString(),
            ...$additionalAgruments,
        ]);

        $this->setTimeout(null);
    }

    public function getCombination(): Combination
    {
        return $this->combination;
    }
}
