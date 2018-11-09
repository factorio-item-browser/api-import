<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Combination;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Database\Constant\TranslationType;
use FactorioItemBrowser\Api\Database\Entity\ModCombination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Exception\ImportException;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Api\Import\Importer\Combination\TranslationImporter;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Recipe;
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
     * Tests the getTranslationsFromCombination method.
     * @throws ReflectionException
     * @covers ::getTranslationsFromCombination
     */
    public function testGetTranslationsFromCombination(): void
    {
        $itemHashes = ['abc', 'def'];
        $machineHashes = ['ghi', 'jkl'];
        $recipeHashes = ['mno', 'pqr'];

        $exportCombination = new ExportCombination();
        $exportCombination->setItemHashes($itemHashes)
                          ->setMachineHashes($machineHashes)
                          ->setRecipeHashes($recipeHashes);

        /* @var DatabaseCombination $databaseCombination */
        $databaseCombination = $this->createMock(DatabaseCombination::class);
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
                         ->setMethods([
                             'createTranslationAggregator',
                             'copyNotRelatedTranslations',
                             'processItems',
                             'processMachines',
                             'processRecipes',
                         ])
                         ->disableOriginalConstructor()
                         ->getMock();
        $importer->expects($this->once())
                 ->method('createTranslationAggregator')
                 ->with($databaseCombination)
                 ->willReturn($translationAggregator);
        $importer->expects($this->once())
                 ->method('copyNotRelatedTranslations')
                 ->with($translationAggregator, $databaseCombination);
        $importer->expects($this->once())
                 ->method('processItems')
                 ->with($translationAggregator, $itemHashes);
        $importer->expects($this->once())
                 ->method('processMachines')
                 ->with($translationAggregator, $machineHashes);
        $importer->expects($this->once())
                 ->method('processRecipes')
                 ->with($translationAggregator, $recipeHashes);

        $result = $this->invokeMethod(
            $importer,
            'getTranslationsFromCombination',
            $exportCombination,
            $databaseCombination
        );

        $this->assertSame($translations, $result);
    }

    /**
     * Tests the copyNotRelatedTranslations method.
     * @throws ReflectionException
     * @covers ::copyNotRelatedTranslations
     */
    public function testCopyNotRelatedTranslations(): void
    {
        /* @var DatabaseCombination|MockObject $databaseCombination */
        $databaseCombination = $this->getMockBuilder(DatabaseCombination::class)
                                    ->setMethods(['getTranslations'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $translation1 = new Translation($databaseCombination, 'foo', TranslationType::ITEM, 'bar');
        $translation2 = new Translation($databaseCombination, 'foo', TranslationType::MOD, 'bar');

        $databaseCombination->expects($this->once())
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
        /* @var RegistryService $registryService */
        $registryService = $this->createMock(RegistryService::class);

        $importer = new TranslationImporter($entityManager, $registryService);

        $this->invokeMethod($importer, 'copyNotRelatedTranslations', $translationAggregator, $databaseCombination);
    }

    /**
     * Tests the processItems method.
     * @throws ReflectionException
     * @covers ::processItems
     */
    public function testProcessItems(): void
    {
        $item1 = new Item();
        $item1->setType('abc')
              ->setName('def')
              ->setProvidesMachineLocalisation(true)
              ->setProvidesRecipeLocalisation(false);
        $item1->getLabels()->setTranslation('en', 'ghi');
        $item1->getDescriptions()->setTranslation('en', 'jkl');

        $item2 = new Item();
        $item2->setType('mno')
              ->setName('pqr')
              ->setProvidesMachineLocalisation(false)
              ->setProvidesRecipeLocalisation(true);
        $item2->getLabels()->setTranslation('de', 'stu');
        $item2->getDescriptions()->setTranslation('de', 'vwx');

        $itemHashes = ['yza', 'bcd'];

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getItem'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(2))
                        ->method('getItem')
                        ->withConsecutive(
                            ['yza'],
                            ['bcd']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $item1,
                            $item2
                        );

        /* @var TranslationAggregator|MockObject $translationAggregator */
        $translationAggregator = $this->getMockBuilder(TranslationAggregator::class)
                                      ->setMethods([
                                          'applyLocalisedStringToValue',
                                          'applyLocalisedStringToDescription',
                                          'applyLocalisedStringToDuplicationFlags'
                                      ])
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $translationAggregator->expects($this->exactly(2))
                              ->method('applyLocalisedStringToValue')
                              ->withConsecutive(
                                  [$item1->getLabels(), 'abc', 'def'],
                                  [$item2->getLabels(), 'mno', 'pqr']
                              );
        $translationAggregator->expects($this->exactly(2))
                              ->method('applyLocalisedStringToDescription')
                              ->withConsecutive(
                                  [$item1->getDescriptions(), 'abc', 'def'],
                                  [$item2->getDescriptions(), 'mno','pqr']
                              );
        $translationAggregator->expects($this->exactly(4))
                              ->method('applyLocalisedStringToDuplicationFlags')
                              ->withConsecutive(
                                  [$item1->getLabels(), 'abc', 'def', true, false],
                                  [$item1->getDescriptions(), 'abc', 'def', true, false],
                                  [$item2->getLabels(), 'mno','pqr', false, true],
                                  [$item2->getDescriptions(), 'mno', 'pqr', false, true]
                              );

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        $importer = new TranslationImporter($entityManager, $registryService);
        $this->invokeMethod($importer, 'processItems', $translationAggregator, $itemHashes);
    }

    /**
     * Tests the processMachines method.
     * @throws ReflectionException
     * @covers ::processMachines
     */
    public function testProcessMachines(): void
    {
        $machine1 = new Machine();
        $machine1->setName('abc');
        $machine1->getLabels()->setTranslation('en', 'def');
        $machine1->getDescriptions()->setTranslation('en', 'ghi');

        $machine2 = new Machine();
        $machine2->setName('jkl');
        $machine2->getLabels()->setTranslation('de', 'mno');
        $machine2->getDescriptions()->setTranslation('de', 'pqr');

        $machineHashes = ['stu', 'vwx'];

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getMachine'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(2))
                        ->method('getMachine')
                        ->withConsecutive(
                            ['stu'],
                            ['vwx']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $machine1,
                            $machine2
                        );

        /* @var TranslationAggregator|MockObject $translationAggregator */
        $translationAggregator = $this->getMockBuilder(TranslationAggregator::class)
                                      ->setMethods([
                                          'applyLocalisedStringToValue',
                                          'applyLocalisedStringToDescription'
                                      ])
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $translationAggregator->expects($this->exactly(2))
                              ->method('applyLocalisedStringToValue')
                              ->withConsecutive(
                                  [$machine1->getLabels(), TranslationType::MACHINE, 'abc'],
                                  [$machine2->getLabels(), TranslationType::MACHINE, 'jkl']
                              );
        $translationAggregator->expects($this->exactly(2))
                              ->method('applyLocalisedStringToDescription')
                              ->withConsecutive(
                                  [$machine1->getDescriptions(), TranslationType::MACHINE, 'abc'],
                                  [$machine2->getDescriptions(), TranslationType::MACHINE, 'jkl']
                              );

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        $importer = new TranslationImporter($entityManager, $registryService);
        $this->invokeMethod($importer, 'processMachines', $translationAggregator, $machineHashes);
    }
    
    
    /**
     * Tests the processRecipes method.
     * @throws ReflectionException
     * @covers ::processRecipes
     */
    public function testProcessRecipes(): void
    {
        $recipe1 = new Recipe();
        $recipe1->setName('abc');
        $recipe1->getLabels()->setTranslation('en', 'def');
        $recipe1->getDescriptions()->setTranslation('en', 'ghi');

        $recipe2 = new Recipe();
        $recipe2->setName('jkl');
        $recipe2->getLabels()->setTranslation('de', 'mno');
        $recipe2->getDescriptions()->setTranslation('de', 'pqr');

        $recipeHashes = ['stu', 'vwx'];

        /* @var RegistryService|MockObject $registryService */
        $registryService = $this->getMockBuilder(RegistryService::class)
                                ->setMethods(['getRecipe'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $registryService->expects($this->exactly(2))
                        ->method('getRecipe')
                        ->withConsecutive(
                            ['stu'],
                            ['vwx']
                        )
                        ->willReturnOnConsecutiveCalls(
                            $recipe1,
                            $recipe2
                        );

        /* @var TranslationAggregator|MockObject $translationAggregator */
        $translationAggregator = $this->getMockBuilder(TranslationAggregator::class)
                                      ->setMethods([
                                          'applyLocalisedStringToValue',
                                          'applyLocalisedStringToDescription'
                                      ])
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $translationAggregator->expects($this->exactly(2))
                              ->method('applyLocalisedStringToValue')
                              ->withConsecutive(
                                  [$recipe1->getLabels(), TranslationType::RECIPE, 'abc'],
                                  [$recipe2->getLabels(), TranslationType::RECIPE, 'jkl']
                              );
        $translationAggregator->expects($this->exactly(2))
                              ->method('applyLocalisedStringToDescription')
                              ->withConsecutive(
                                  [$recipe1->getDescriptions(), TranslationType::RECIPE, 'abc'],
                                  [$recipe2->getDescriptions(), TranslationType::RECIPE, 'jkl']
                              );

        /* @var EntityManager $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        $importer = new TranslationImporter($entityManager, $registryService);
        $this->invokeMethod($importer, 'processRecipes', $translationAggregator, $recipeHashes);
    }
}
