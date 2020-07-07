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
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;

/**
 * The PHPUnit test of the ModTranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\ModTranslationImporter
 */
class ModTranslationImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

    /**
     * The mocked id calculator.
     * @var IdCalculator&MockObject
     */
    protected $idCalculator;

    /**
     * The mocked translation repository.
     * @var TranslationRepository&MockObject
     */
    protected $translationRepository;

    /**
     * The mocked validator.
     * @var Validator&MockObject
     */
    protected $validator;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->translationRepository = $this->createMock(TranslationRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the prepare method.
     * @throws DBALException
     * @covers ::prepare
     */
    public function testPrepare(): void
    {
        $combinationId = $this->createMock(Uuid::class);

        $combination = new DatabaseCombination();
        $combination->setId($combinationId);

        $this->translationRepository->expects($this->once())
                                    ->method('clearCrossTable')
                                    ->with($this->identicalTo($combinationId));

        $importer = new ModTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->translationRepository,
            $this->validator,
        );
        $importer->prepare($combination);
    }


    /**
     * Tests the getExportEntities method.
     * @throws ReflectionException
     * @covers ::getExportEntities
     */
    public function testGetExportEntities(): void
    {
        $mod1 = $this->createMock(ExportMod::class);
        $mod2 = $this->createMock(ExportMod::class);
        $mod3 = $this->createMock(ExportMod::class);

        $combination = new ExportCombination();
        $combination->setMods([$mod1, $mod2, $mod3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new ModTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->translationRepository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals([$mod1, $mod2, $mod3], iterator_to_array($result));
    }
    
    /**
     * Tests the createTranslationsForEntity method.
     * @throws ReflectionException
     * @covers ::createTranslationsForEntity
     */
    public function testCreateTranslationsForEntity(): void
    {
        $exportData = $this->createMock(ExportData::class);
        
        $mod = new ExportMod();
        $mod->setName('abc');

        $translations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];
        
        /* @var ModTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(ModTranslationImporter::class)
                         ->onlyMethods(['createTranslationsFromLocalisedStrings'])
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->translationRepository,
                             $this->validator,
                         ])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('createTranslationsFromLocalisedStrings')
                 ->with(
                     $this->identicalTo(EntityType::MOD),
                     $this->identicalTo('abc'),
                     $this->identicalTo($mod->getTitles()),
                     $this->identicalTo($mod->getDescriptions()),
                 )
                 ->willReturn($translations);

        $result = $this->invokeMethod($importer, 'createTranslationsForEntity', $exportData, $mod);

        $this->assertSame($translations, $result);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $this->translationRepository->expects($this->once())
                                    ->method('removeOrphans');

        $importer = new ModTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->translationRepository,
            $this->validator,
        );
        $importer->cleanup();
    }
}
