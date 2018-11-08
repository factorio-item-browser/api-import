<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Importer\Combination\TranslationImporter;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the TranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Combination\TranslationImporter
 */
class TranslationImporterTest extends TestCase
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
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new TranslationImporter($entityManager, $registryService);

        $this->assertSame($entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($registryService, $this->extractProperty($importer, 'registryService'));
        $this->assertSame([], $this->extractProperty($importer, 'translations'));
    }
    
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

        /* @var ExportCombination $exportCombination */
        $exportCombination = $this->createMock(ExportCombination::class);
        /* @var Collection $translationCollection */
        $translationCollection = $this->createMock(Collection::class);

        /* @var DatabaseCombination|MockObject $databaseCombination */
        $databaseCombination = $this->getMockBuilder(DatabaseCombination::class)
                                    ->setMethods(['getTranslations'])
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $databaseCombination->expects($this->once())
                            ->method('getTranslations')
                            ->willReturn($translationCollection);

        /* @var TranslationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(TranslationImporter::class)
                         ->setMethods([
                             'getTranslationsFromCombination',
                             'getExistingTranslations',
                             'persistEntities',
                             'assignEntitiesToCollection'
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getTranslationsFromCombination')
                 ->with($exportCombination)
                 ->willReturn($newTranslations);
        $importer->expects($this->once())
                 ->method('getExistingTranslations')
                 ->with($newTranslations, $databaseCombination)
                 ->willReturn($existingTranslations);
        $importer->expects($this->once())
                 ->method('persistEntities')
                 ->with($newTranslations, $existingTranslations)
                 ->willReturn($persistedTranslations);
        $importer->expects($this->once())
                 ->method('assignEntitiesToCollection')
                 ->with($persistedTranslations, $translationCollection);

        $importer->import($exportCombination, $databaseCombination);
    }

    /**
     * Tests the getIdentifier method.
     * @throws ReflectionException
     * @covers ::getIdentifier
     */
    public function testGetIdentifier(): void
    {
        $locale = 'abc';
        $type = 'def';
        $name = 'ghi';
        $expectedResult = 'abc|def|ghi';

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new TranslationImporter($entityManager, $registryService);
        $result = $this->invokeMethod($importer, 'getIdentifier', $locale, $type, $name);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getIdentifierOfTranslation method.
     * @throws ReflectionException
     * @covers ::getIdentifierOfTranslation
     */
    public function testGetIdentifierOfTranslation(): void
    {
        $translation = new Translation($this->createMock(DatabaseCombination::class), 'abc', 'def', 'ghi');
        $identifier = 'jkl';

        /* @var TranslationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(TranslationImporter::class)
                         ->setMethods(['getIdentifier'])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('getIdentifier')
                 ->with('abc', 'def', 'ghi')
                 ->willReturn($identifier);

        $result = $this->invokeMethod($importer, 'getIdentifierOfTranslation', $translation);

        $this->assertSame($identifier, $result);
    }
}
