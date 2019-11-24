<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Command\ImportTranslationsCommand;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Mod;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the ImportTranslationsCommand class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Command\ImportTranslationsCommand
 */
class ImportTranslationsCommandTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked combination repository.
     * @var CombinationRepository&MockObject
     */
    protected $combinationRepository;

    /**
     * The mocked console.
     * @var Console&MockObject
     */
    protected $console;

    /**
     * The mocked export data service.
     * @var ExportDataService&MockObject
     */
    protected $exportDataService;

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
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->combinationRepository = $this->createMock(CombinationRepository::class);
        $this->console = $this->createMock(Console::class);
        $this->exportDataService = $this->createMock(ExportDataService::class);
        $this->idCalculator = $this->createMock(IdCalculator::class);
        $this->translationRepository = $this->createMock(TranslationRepository::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $command = new ImportTranslationsCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $this->idCalculator,
            $this->translationRepository
        );

        $this->assertSame($this->combinationRepository, $this->extractProperty($command, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($command, 'console'));
        $this->assertSame($this->exportDataService, $this->extractProperty($command, 'exportDataService'));
        $this->assertSame($this->idCalculator, $this->extractProperty($command, 'idCalculator'));
        $this->assertSame($this->translationRepository, $this->extractProperty($command, 'translationRepository'));
    }

    /**
     * Tests the getLabel method.
     * @throws ReflectionException
     * @covers ::getLabel
     */
    public function testGetLabel(): void
    {
        $command = new ImportTranslationsCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $this->idCalculator,
            $this->translationRepository
        );
        $result = $this->invokeMethod($command, 'getLabel');

        $this->assertSame('Processing the translations', $result);
    }

    /**
     * Tests the import method.
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        $translations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];

        /* @var UuidInterface&MockObject $combinationId */
        $combinationId = $this->createMock(UuidInterface::class);

        $combination = new DatabaseCombination();
        $combination->setId($combinationId);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);

        $this->console->expects($this->once())
                      ->method('writeAction')
                      ->with($this->identicalTo('Persisting processed data'));
        
        $this->translationRepository->expects($this->once())
                                    ->method('persistTranslationsToCombination')
                                    ->with($this->identicalTo($combinationId), $this->identicalTo($translations));

        /* @var ImportTranslationsCommand&MockObject $command */
        $command = $this->getMockBuilder(ImportTranslationsCommand::class)
                        ->onlyMethods(['process', 'hydrateIds'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->exportDataService,
                            $this->idCalculator,
                            $this->translationRepository,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('process')
                ->with($this->identicalTo($exportData))
                ->willReturn($translations);
        $command->expects($this->once())
                ->method('hydrateIds')
                ->with($this->identicalTo($translations));

        $this->invokeMethod($command, 'import', $exportData, $combination);
    }

    /**
     * Tests the process method.
     * @throws ReflectionException
     * @covers ::process
     */
    public function testProcess(): void
    {
        $mods = [
            $this->createMock(Mod::class),
            $this->createMock(Mod::class),
        ];
        $items = [
            $this->createMock(Item::class),
            $this->createMock(Item::class),
        ];
        $machines = [
            $this->createMock(Machine::class),
            $this->createMock(Machine::class),
        ];
        $recipes = [
            $this->createMock(Recipe::class),
            $this->createMock(Recipe::class),
        ];
        $translations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];

        $combination = new ExportCombination();
        $combination->setMods($mods)
                    ->setItems($items)
                    ->setMachines($machines)
                    ->setRecipes($recipes);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->any())
                   ->method('getCombination')
                   ->willReturn($combination);

        /* @var TranslationAggregator&MockObject $translationAggregator */
        $translationAggregator = $this->createMock(TranslationAggregator::class);
        $translationAggregator->expects($this->once())
                              ->method('optimize');
        $translationAggregator->expects($this->once())
                              ->method('getTranslations')
                              ->willReturn($translations);

        /* @var ImportTranslationsCommand&MockObject $command */
        $command = $this->getMockBuilder(ImportTranslationsCommand::class)
                        ->onlyMethods([
                            'createTranslationAggregator',
                            'processMods',
                            'processItems',
                            'processMachines',
                            'processRecipes',
                        ])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->exportDataService,
                            $this->idCalculator,
                            $this->translationRepository,
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('createTranslationAggregator')
                ->willReturn($translationAggregator);
        $command->expects($this->once())
                ->method('processMods')
                ->with($this->identicalTo($translationAggregator), $this->identicalTo($mods));
        $command->expects($this->once())
                ->method('processItems')
                ->with($this->identicalTo($translationAggregator), $this->identicalTo($items));
        $command->expects($this->once())
                ->method('processMachines')
                ->with($this->identicalTo($translationAggregator), $this->identicalTo($machines));
        $command->expects($this->once())
                ->method('processRecipes')
                ->with($this->identicalTo($translationAggregator), $this->identicalTo($recipes));

        $result = $this->invokeMethod($command, 'process', $exportData);

        $this->assertSame($translations, $result);
    }

    /**
     * Tests the createTranslationAggregator method.
     * @throws ReflectionException
     * @covers ::createTranslationAggregator
     */
    public function testCreateTranslationAggregator(): void
    {
        $expectedResult = new TranslationAggregator();

        $command = new ImportTranslationsCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $this->idCalculator,
            $this->translationRepository
        );
        $result = $this->invokeMethod($command, 'createTranslationAggregator');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the processMods method.
     * @throws ReflectionException
     * @covers ::processMods
     */
    public function testProcessMods(): void
    {
        $mod1 = new Mod();
        $mod1->setName('abc');

        $mod2 = new Mod();
        $mod2->setName('def');

        $mods = [$mod1, $mod2];

        /* @var TranslationAggregator&MockObject $translationAggregator */
        $translationAggregator = $this->createMock(TranslationAggregator::class);
        $translationAggregator->expects($this->exactly(2))
                              ->method('add')
                              ->withConsecutive(
                                  [
                                      $this->identicalTo(EntityType::MOD),
                                      $this->identicalTo('abc'),
                                      $this->identicalTo($mod1->getTitles()),
                                      $this->identicalTo($mod1->getDescriptions()),
                                  ],
                                  [
                                      $this->identicalTo(EntityType::MOD),
                                      $this->identicalTo('def'),
                                      $this->identicalTo($mod2->getTitles()),
                                      $this->identicalTo($mod2->getDescriptions()),
                                  ]
                              );
        
        $this->console->expects($this->once())
                      ->method('writeAction')
                      ->with($this->identicalTo('Processing mods'));

        $command = new ImportTranslationsCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $this->idCalculator,
            $this->translationRepository
        );
        $this->invokeMethod($command, 'processMods', $translationAggregator, $mods);
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
              ->setName('def');

        $item2 = new Item();
        $item2->setType('ghi')
              ->setName('jkl');

        $items = [$item1, $item2];

        /* @var TranslationAggregator&MockObject $translationAggregator */
        $translationAggregator = $this->createMock(TranslationAggregator::class);
        $translationAggregator->expects($this->exactly(2))
                              ->method('add')
                              ->withConsecutive(
                                  [
                                      $this->identicalTo('abc'),
                                      $this->identicalTo('def'),
                                      $this->identicalTo($item1->getLabels()),
                                      $this->identicalTo($item1->getDescriptions()),
                                  ],
                                  [
                                      $this->identicalTo('ghi'),
                                      $this->identicalTo('jkl'),
                                      $this->identicalTo($item2->getLabels()),
                                      $this->identicalTo($item2->getDescriptions()),
                                  ]
                              );
        
        $this->console->expects($this->once())
                      ->method('writeAction')
                      ->with($this->identicalTo('Processing items'));

        $command = new ImportTranslationsCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $this->idCalculator,
            $this->translationRepository
        );
        $this->invokeMethod($command, 'processItems', $translationAggregator, $items);
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

        $machine2 = new Machine();
        $machine2->setName('def');

        $machines = [$machine1, $machine2];

        /* @var TranslationAggregator&MockObject $translationAggregator */
        $translationAggregator = $this->createMock(TranslationAggregator::class);
        $translationAggregator->expects($this->exactly(2))
                              ->method('add')
                              ->withConsecutive(
                                  [
                                      $this->identicalTo(EntityType::MACHINE),
                                      $this->identicalTo('abc'),
                                      $this->identicalTo($machine1->getLabels()),
                                      $this->identicalTo($machine1->getDescriptions()),
                                  ],
                                  [
                                      $this->identicalTo(EntityType::MACHINE),
                                      $this->identicalTo('def'),
                                      $this->identicalTo($machine2->getLabels()),
                                      $this->identicalTo($machine2->getDescriptions()),
                                  ]
                              );
        
        $this->console->expects($this->once())
                      ->method('writeAction')
                      ->with($this->identicalTo('Processing machines'));

        $command = new ImportTranslationsCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $this->idCalculator,
            $this->translationRepository
        );
        $this->invokeMethod($command, 'processMachines', $translationAggregator, $machines);
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

        $recipe2 = new Recipe();
        $recipe2->setName('def');

        $recipes = [$recipe1, $recipe2];

        /* @var TranslationAggregator&MockObject $translationAggregator */
        $translationAggregator = $this->createMock(TranslationAggregator::class);
        $translationAggregator->expects($this->exactly(2))
                              ->method('add')
                              ->withConsecutive(
                                  [
                                      $this->identicalTo(EntityType::RECIPE),
                                      $this->identicalTo('abc'),
                                      $this->identicalTo($recipe1->getLabels()),
                                      $this->identicalTo($recipe1->getDescriptions()),
                                  ],
                                  [
                                      $this->identicalTo(EntityType::RECIPE),
                                      $this->identicalTo('def'),
                                      $this->identicalTo($recipe2->getLabels()),
                                      $this->identicalTo($recipe2->getDescriptions()),
                                  ]
                              );
        
        $this->console->expects($this->once())
                      ->method('writeAction')
                      ->with($this->identicalTo('Processing recipes'));

        $command = new ImportTranslationsCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $this->idCalculator,
            $this->translationRepository
        );
        $this->invokeMethod($command, 'processRecipes', $translationAggregator, $recipes);
    }
    
    /**
     * Tests the hydrateIds method.
     * @throws ReflectionException
     * @covers ::hydrateIds
     */
    public function testHydrateIds(): void
    {
        /* @var UuidInterface&MockObject $translationId1 */
        $translationId1 = $this->createMock(UuidInterface::class);
        /* @var UuidInterface&MockObject $translationId2 */
        $translationId2 = $this->createMock(UuidInterface::class);
        
        /* @var Translation&MockObject $translation1 */
        $translation1 = $this->createMock(Translation::class);
        $translation1->expects($this->once())
                     ->method('setId')
                     ->with($this->identicalTo($translationId1));
        
        /* @var Translation&MockObject $translation2 */
        $translation2 = $this->createMock(Translation::class);
        $translation2->expects($this->once())
                     ->method('setId')
                     ->with($this->identicalTo($translationId2));
        
        $translations = [$translation1, $translation2];
        
        $this->idCalculator->expects($this->exactly(2))
                           ->method('calculateIdOfTranslation')
                           ->withConsecutive(
                               [$this->identicalTo($translation1)],
                               [$this->identicalTo($translation2)]
                           )
                           ->willReturnOnConsecutiveCalls(
                               $translationId1,
                               $translationId2
                           );

        $command = new ImportTranslationsCommand(
            $this->combinationRepository,
            $this->console,
            $this->exportDataService,
            $this->idCalculator,
            $this->translationRepository
        );
        $this->invokeMethod($command, 'hydrateIds', $translations);
    }
}
