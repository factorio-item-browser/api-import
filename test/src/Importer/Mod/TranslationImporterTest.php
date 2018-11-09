<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Constant\TranslationType;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Api\Import\Importer\Mod\TranslationImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use FactorioItemBrowser\ExportData\Entity\Mod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the TranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Mod\TranslationImporter
 */
class TranslationImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the import method.
     * @throws ImportException
     * @covers ::import
     */
    public function testImport(): void
    {
        $newTranslations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];
        $existingTranslations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];
        $persistedTranslations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];

        /* @var DatabaseMod $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);
        /* @var ExportMod $exportMod */
        $exportMod = $this->createMock(ExportMod::class);
        /* @var Collection $translationCollection */
        $translationCollection = $this->createMock(Collection::class);

        /* @var DatabaseCombination|MockObject $baseCombination */
        $baseCombination = $this->getMockBuilder(DatabaseCombination::class)
                                ->setMethods(['getTranslations'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $baseCombination->expects($this->once())
                        ->method('getTranslations')
                        ->willReturn($translationCollection);

        /* @var TranslationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(TranslationImporter::class)
                         ->setMethods([
                             'findBaseCombination',
                             'getTranslationsFromMod',
                             'getExistingTranslations',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('findBaseCombination')
                 ->with($databaseMod)
                 ->willReturn($baseCombination);
        $importer->expects($this->once())
                 ->method('getTranslationsFromMod')
                 ->with($exportMod, $baseCombination)
                 ->willReturn($newTranslations);
        $importer->expects($this->once())
                 ->method('getExistingTranslations')
                 ->with($newTranslations, $baseCombination)
                 ->willReturn($existingTranslations);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newTranslations, $existingTranslations)
                 ->willReturn($persistedTranslations);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedTranslations, $translationCollection);

        $importer->import($exportMod, $databaseMod);
    }

    /**
     * Provides the data for the findBaseCombination test.
     * @return array
     */
    public function provideFindBaseCombination(): array
    {
        /* @var DatabaseMod $databaseMod */
        $databaseMod = $this->createMock(DatabaseMod::class);

        $combination1 = new DatabaseCombination($databaseMod, 'abc');
        $combination1->setOptionalModIds([42, 1337]);

        $combination2 = new DatabaseCombination($databaseMod, 'def');
        $combination2->setOptionalModIds([]);

        return [
            [[$combination1, $combination2], false, $combination2],
            [[$combination1], true, null],
        ];
    }

    /**
     * Tests the findBaseCombination method.
     * @covers ::findBaseCombination
     * @param array|DatabaseCombination[] $combinations
     * @param bool $expectException
     * @param DatabaseCombination|null $expectedResult
     * @throws ReflectionException
     * @dataProvider provideFindBaseCombination
     */
    public function testFindBaseCombination(
        array $combinations,
        bool $expectException,
        ?DatabaseCombination $expectedResult
    ): void {
        /* @var DatabaseMod|MockObject $databaseMod */
        $databaseMod = $this->getMockBuilder(DatabaseMod::class)
                            ->setMethods(['getCombinations'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $databaseMod->expects($this->once())
                    ->method('getCombinations')
                    ->willReturn(new ArrayCollection($combinations));

        if ($expectException) {
            $this->expectException(ImportException::class);
        }

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        $importer = new TranslationImporter($entityManager);
        $result = $this->invokeMethod($importer, 'findBaseCombination', $databaseMod);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getTranslationsFromMod method.
     * @throws ReflectionException
     * @covers ::getTranslationsFromMod
     */
    public function testGetTranslationsFromMod(): void
    {
        /* @var ExportMod $exportMod */
        $exportMod = $this->createMock(ExportMod::class);
        /* @var ModCombination $baseCombination */
        $baseCombination = $this->createMock(ModCombination::class);
        $translations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];

        /* @var TranslationAggregator|MockObject $translationAggregator */
        $translationAggregator = $this->getMockBuilder(TranslationAggregator::class)
                                      ->setMethods(['getAggregatedTranslations'])
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $translationAggregator->expects($this->once())
                              ->method('getAggregatedTranslations')
                              ->willReturn($translations);

        /* @var TranslationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(TranslationImporter::class)
                         ->setMethods(['createTranslationAggregator', 'copyNotRelatedTranslations', 'processMod'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('createTranslationAggregator')
                 ->with($baseCombination)
                 ->willReturn($translationAggregator);
        $importer->expects($this->once())
                 ->method('copyNotRelatedTranslations')
                 ->with($translationAggregator, $baseCombination);
        $importer->expects($this->once())
                 ->method('processMod')
                 ->with($translationAggregator, $exportMod);

        $result = $this->invokeMethod($importer, 'getTranslationsFromMod', $exportMod, $baseCombination);
        $this->assertSame($translations, $result);
    }


    /**
     * Tests the copyNotRelatedTranslations method.
     * @throws ReflectionException
     * @covers ::copyNotRelatedTranslations
     */
    public function testCopyNotRelatedTranslations(): void
    {
        /* @var DatabaseCombination|MockObject $baseCombination */
        $baseCombination = $this->getMockBuilder(DatabaseCombination::class)
                                ->setMethods(['getTranslations'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $translation1 = new Translation($baseCombination, 'foo', TranslationType::MOD, 'bar');
        $translation2 = new Translation($baseCombination, 'foo', TranslationType::ITEM, 'bar');

        $baseCombination->expects($this->once())
                            ->method('getTranslations')
                            ->willReturn(new ArrayCollection([$translation1, $translation2]));

        /* @var TranslationAggregator|MockObject $translationAggregator */
        $translationAggregator = $this->getMockBuilder(TranslationAggregator::class)
                                      ->setMethods(['addTranslation'])
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $translationAggregator->expects($this->once())
                              ->method('addTranslation')
                              ->with($translation2);

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        $importer = new TranslationImporter($entityManager);

        $this->invokeMethod($importer, 'copyNotRelatedTranslations', $translationAggregator, $baseCombination);
    }

    /**
     * Tests the processMod method.
     * @throws ReflectionException
     * @covers ::processMod
     */
    public function testProcessMod(): void
    {
        $mod = new Mod();
        $mod->setName('abc');
        $mod->getTitles()->setTranslation('en', 'def');
        $mod->getDescriptions()->setTranslation('de', 'ghi');

        /* @var TranslationAggregator|MockObject $translationAggregator */
        $translationAggregator = $this->getMockBuilder(TranslationAggregator::class)
                                      ->setMethods([
                                          'applyLocalisedStringToValue',
                                          'applyLocalisedStringToDescription'
                                      ])
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $translationAggregator->expects($this->once())
                              ->method('applyLocalisedStringToValue')
                              ->with($mod->getTitles(), TranslationType::MOD, 'abc');
        $translationAggregator->expects($this->once())
                              ->method('applyLocalisedStringToDescription')
                              ->with($mod->getDescriptions(), TranslationType::MOD, 'abc');

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        $importer = new TranslationImporter($entityManager);
        $this->invokeMethod($importer, 'processMod', $translationAggregator, $mod);
    }
}
