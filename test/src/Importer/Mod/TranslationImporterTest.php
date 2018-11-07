<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use FactorioItemBrowser\Api\Database\Entity\Mod as DatabaseMod;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\Importer\Mod\TranslationImporter;
use FactorioItemBrowser\ExportData\Entity\Mod as ExportMod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
}
