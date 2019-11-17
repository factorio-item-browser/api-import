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
    /**
     * The combination of the process.
     * @var Combination
     */
    protected $combination;

    /**
     * Initializes the process.
     * @param string $commandName
     * @param Combination $combination
     */
    public function __construct(string $commandName, Combination $combination)
    {
        $this->combination = $combination;

        parent::__construct([
            'php',
            $_SERVER['SCRIPT_FILENAME'],
            $commandName,
            $combination->getId()->toString(),
        ]);

        $this->setEnv(['SUBCMD' => 1]);
        $this->setTimeout(null);
    }

    /**
     * Returns the combination of the process.
     * @return Combination
     */
    public function getCombination(): Combination
    {
        return $this->combination;
    }
}
