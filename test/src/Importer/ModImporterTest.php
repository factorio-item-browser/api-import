<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\ModImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the ModImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\ModImporter
 */
class ModImporterTest extends TestCase
{
    use ReflectionTrait;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var IdCalculator&MockObject */
    private IdCalculator $idCalculator;
    /** @var ModRepository&MockObject */
    private ModRepository $repository;
    /** @var Validator&MockObject */
    private Validator $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->repository = $this->createMock(ModRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return ModImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): ModImporter
    {
        return $this->getMockBuilder(ModImporter::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->entityManager,
                        $this->idCalculator,
                        $this->repository,
                        $this->validator,
                    ])
                    ->getMock();
    }

    /**
     * @throws ReflectionException
     */
    public function testGetCollectionFromCombination(): void
    {
        $mods = $this->createMock(Collection::class);

        $combination = $this->createMock(DatabaseCombination::class);
        $combination->expects($this->once())
                    ->method('getMods')
                    ->willReturn($mods);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getCollectionFromCombination', $combination);

        $this->assertSame($mods, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetExportEntities(): void
    {
        $mod1 = $this->createMock(ExportMod::class);
        $mod2 = $this->createMock(ExportMod::class);
        $mod3 = $this->createMock(ExportMod::class);

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getMods()->add($mod1)
                              ->add($mod2)
                              ->add($mod3);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getExportEntities', $exportData);

        $this->assertEquals([$mod1, $mod2, $mod3], iterator_to_array($result));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateDatabaseEntity(): void
    {
        $modId = $this->createMock(UuidInterface::class);

        $exportMod = new ExportMod();
        $exportMod->name = 'abc';
        $exportMod->version = '1.2.3';
        $exportMod->author = 'def';

        $expectedDatabaseMod = new DatabaseMod();
        $expectedDatabaseMod->setName('abc')
                            ->setVersion('1.2.3')
                            ->setAuthor('def');

        $expectedResult = new DatabaseMod();
        $expectedResult->setId($modId)
                       ->setName('abc')
                       ->setVersion('1.2.3')
                       ->setAuthor('def');

        $this->idCalculator->expects($this->once())
                           ->method('calculateIdOfMod')
                           ->with($this->equalTo($expectedDatabaseMod))
                           ->willReturn($modId);

        $this->validator->expects($this->once())
                        ->method('validateMod')
                        ->with($this->equalTo($expectedDatabaseMod));

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'createDatabaseEntity', $exportMod);

        $this->assertEquals($expectedResult, $result);
    }
}
