<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\ModTranslationImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;

/**
 * The PHPUnit test of the ModTranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\ModTranslationImporter
 */
class ModTranslationImporterTest extends TestCase
{
    use ReflectionTrait;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var IdCalculator&MockObject */
    private IdCalculator $idCalculator;
    /** @var TranslationRepository&MockObject */
    private TranslationRepository $repository;
    /** @var Validator&MockObject */
    private Validator $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->repository = $this->createMock(TranslationRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * @param array<string> $mockedMethods
     * @return ModTranslationImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): ModTranslationImporter
    {
        return $this->getMockBuilder(ModTranslationImporter::class)
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
     * @throws DBALException
     */
    public function testPrepare(): void
    {
        $combinationId = $this->createMock(Uuid::class);

        $combination = new DatabaseCombination();
        $combination->setId($combinationId);

        $this->repository->expects($this->once())
                         ->method('clearCrossTable')
                         ->with($this->identicalTo($combinationId));

        $instance = $this->createInstance();
        $instance->prepare($combination);
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
    public function testCreateTranslationsForEntity(): void
    {
        $exportData = $this->createMock(ExportData::class);
        
        $mod = new ExportMod();
        $mod->name = 'abc';

        $translations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];
        
        $instance = $this->createInstance(['createTranslationsFromLocalisedStrings']);
        $instance->expects($this->once())
                 ->method('createTranslationsFromLocalisedStrings')
                 ->with(
                     $this->identicalTo(EntityType::MOD),
                     $this->identicalTo('abc'),
                     $this->identicalTo($mod->titles),
                     $this->identicalTo($mod->descriptions),
                 )
                 ->willReturn($translations);

        $result = $this->invokeMethod($instance, 'createTranslationsForEntity', $exportData, $mod);

        $this->assertSame($translations, $result);
    }

    public function testCleanup(): void
    {
        $this->repository->expects($this->once())
                         ->method('removeOrphans');

        $instance = $this->createInstance();
        $instance->cleanup();
    }
}
