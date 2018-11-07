<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Repository\ModCombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Importer\Generic\CombinationOrderImporter;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the CombinationOrderImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\CombinationOrderImporter
 */
class CombinationOrderImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var ModCombinationRepository $modCombinationRepository */
        $modCombinationRepository = $this->createMock(ModCombinationRepository::class);
        /* @var ModRepository $modRepository */
        $modRepository = $this->createMock(ModRepository::class);

        $importer = new CombinationOrderImporter($entityManager, $modCombinationRepository, $modRepository);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($modCombinationRepository, $this->extractProperty($importer, 'modCombinationRepository'));
        $this->assertSame($modRepository, $this->extractProperty($importer, 'modRepository'));
    }
}
