<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\ItemTranslationImporter;
use FactorioItemBrowser\Common\Constant\RecipeMode;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the ItemTranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\ItemTranslationImporter
 */
class ItemTranslationImporterTest extends TestCase
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
     * The mocked repository.
     * @var TranslationRepository&MockObject
     */
    protected $repository;

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
        $this->repository = $this->createMock(TranslationRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the getExportEntities method.
     * @throws ReflectionException
     * @covers ::getExportEntities
     */
    public function testGetExportEntities(): void
    {
        $item1 = $this->createMock(ExportItem::class);
        $item2 = $this->createMock(ExportItem::class);
        $item3 = $this->createMock(ExportItem::class);

        $combination = new ExportCombination();
        $combination->setItems([$item1, $item2, $item3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new ItemTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals([$item1, $item2, $item3], iterator_to_array($result));
    }  
    
    /**
     * Tests the createTranslationsForEntity method.
     * @throws ReflectionException
     * @covers ::createTranslationsForEntity
     */
    public function testCreateTranslationsForEntity(): void
    {
        $exportData = $this->createMock(ExportData::class);
        
        $item = new ExportItem();
        $item->setType('abc')
             ->setName('def');

        $recipe = $this->createMock(Recipe::class);
        $machine = $this->createMock(Machine::class);
        
        $translations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];
        
        /* @var ItemTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(ItemTranslationImporter::class)
                         ->onlyMethods([
                             'createTranslationsFromLocalisedStrings',
                             'findRecipe',
                             'checkRecipeDuplication',
                             'findMachine',
                             'checkMachineDuplication',
                         ])
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMock();
        $importer->expects($this->once())
                 ->method('createTranslationsFromLocalisedStrings')
                 ->with(
                     $this->identicalTo('abc'),
                     $this->identicalTo('def'),
                     $this->identicalTo($item->getLabels()),
                     $this->identicalTo($item->getDescriptions()),
                 )
                 ->willReturn($translations);
        $importer->expects($this->once())
                 ->method('findRecipe')
                 ->with($this->identicalTo($exportData), $this->identicalTo('def'))
                 ->willReturn($recipe);
        $importer->expects($this->once())
                 ->method('checkRecipeDuplication')
                 ->with($this->identicalTo($translations), $this->identicalTo($recipe));
        $importer->expects($this->once())
                 ->method('findMachine')
                 ->with($this->identicalTo($exportData), $this->identicalTo('def'))
                 ->willReturn($machine);
        $importer->expects($this->once())
                 ->method('checkMachineDuplication')
                 ->with($this->identicalTo($translations), $this->identicalTo($machine));

        $result = $this->invokeMethod($importer, 'createTranslationsForEntity', $exportData, $item);

        $this->assertSame($translations, $result);
    }

    /**
     * Tests the findRecipe method.
     * @throws ReflectionException
     * @covers ::findRecipe
     */
    public function testFindRecipe(): void
    {
        $name = 'def';

        $recipe1 = new Recipe();
        $recipe1->setName('abc')
                ->setMode(RecipeMode::NORMAL);

        $recipe2 = new Recipe();
        $recipe2->setName('def')
                ->setMode(RecipeMode::EXPENSIVE);

        $recipe3 = new Recipe();
        $recipe3->setName('def')
                ->setMode(RecipeMode::NORMAL);

        $combination = new ExportCombination();
        $combination->setRecipes([$recipe1, $recipe2, $recipe3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new ItemTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'findRecipe', $exportData, $name);
        
        $this->assertSame($recipe3, $result);
    }
    
    /**
     * Tests the findRecipe method.
     * @throws ReflectionException
     * @covers ::findRecipe
     */
    public function testFindRecipeWithoutMatch(): void
    {
        $name = 'foo';

        $recipe1 = new Recipe();
        $recipe1->setName('abc');

        $recipe2 = new Recipe();
        $recipe2->setName('def');

        $combination = new ExportCombination();
        $combination->setRecipes([$recipe1, $recipe2]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new ItemTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'findRecipe', $exportData, $name);
        
        $this->assertNull($result);
    }

    /**
     * Tests the checkRecipeDuplication method.
     * @throws ReflectionException
     * @covers ::checkRecipeDuplication
     */
    public function testCheckRecipeDuplication(): void
    {
        $translation1 = new Translation();
        $translation1->setLocale('abc')
                     ->setValue('def')
                     ->setDescription('ghi');

        $translation2 = new Translation();
        $translation2->setLocale('jkl')
                     ->setValue('mno')
                     ->setDescription('pqr');

        $translation3 = new Translation();
        $translation3->setLocale('stu')
                     ->setValue('vwx')
                     ->setDescription('yza');

        $translations = [$translation1, $translation2, $translation3];

        $recipe = new Recipe();
        $recipe->getLabels()->addTranslation('abc', 'def')
                            ->addTranslation('jkl', 'mno');
        $recipe->getDescriptions()->addTranslation('abc', 'ghi')
                                  ->addTranslation('jkl', 'foo');

        $importer = new ItemTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $this->invokeMethod($importer, 'checkRecipeDuplication', $translations, $recipe);

        $this->assertTrue($translation1->getIsDuplicatedByRecipe());
        $this->assertFalse($translation2->getIsDuplicatedByRecipe());
        $this->assertFalse($translation3->getIsDuplicatedByRecipe());
    }

    /**
     * Tests the checkRecipeDuplication method.
     * @throws ReflectionException
     * @covers ::checkRecipeDuplication
     */
    public function testCheckRecipeDuplicationWithoutRecipe(): void
    {
        $translation1 = new Translation();
        $translation1->setLocale('abc')
                     ->setValue('def')
                     ->setDescription('ghi');

        $translation2 = new Translation();
        $translation2->setLocale('jkl')
                     ->setValue('mno')
                     ->setDescription('pqr');

        $translation3 = new Translation();
        $translation3->setLocale('stu')
                     ->setValue('vwx')
                     ->setDescription('yza');

        $translations = [$translation1, $translation2, $translation3];

        $importer = new ItemTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $this->invokeMethod($importer, 'checkRecipeDuplication', $translations, null);

        $this->assertFalse($translation1->getIsDuplicatedByRecipe());
        $this->assertFalse($translation2->getIsDuplicatedByRecipe());
        $this->assertFalse($translation3->getIsDuplicatedByRecipe());
    }

    /**
     * Tests the findMachine method.
     * @throws ReflectionException
     * @covers ::findMachine
     */
    public function testFindMachine(): void
    {
        $name = 'def';

        $machine1 = new Machine();
        $machine1->setName('abc');

        $machine2 = new Machine();
        $machine2->setName('def');

        $combination = new ExportCombination();
        $combination->setMachines([$machine1, $machine2]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new ItemTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'findMachine', $exportData, $name);
        
        $this->assertSame($machine2, $result);
    }
    
    /**
     * Tests the findMachine method.
     * @throws ReflectionException
     * @covers ::findMachine
     */
    public function testFindMachineWithoutMatch(): void
    {
        $name = 'foo';

        $machine1 = new Machine();
        $machine1->setName('abc');

        $machine2 = new Machine();
        $machine2->setName('def');

        $combination = new ExportCombination();
        $combination->setMachines([$machine1, $machine2]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new ItemTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'findMachine', $exportData, $name);
        
        $this->assertNull($result);
    }

    /**
     * Tests the checkMachineDuplication method.
     * @throws ReflectionException
     * @covers ::checkMachineDuplication
     */
    public function testCheckMachineDuplication(): void
    {
        $translation1 = new Translation();
        $translation1->setLocale('abc')
                     ->setValue('def')
                     ->setDescription('ghi');

        $translation2 = new Translation();
        $translation2->setLocale('jkl')
                     ->setValue('mno')
                     ->setDescription('pqr');

        $translation3 = new Translation();
        $translation3->setLocale('stu')
                     ->setValue('vwx')
                     ->setDescription('yza');

        $translations = [$translation1, $translation2, $translation3];

        $machine = new Machine();
        $machine->getLabels()->addTranslation('abc', 'def')
                             ->addTranslation('jkl', 'mno');
        $machine->getDescriptions()->addTranslation('abc', 'ghi')
                                   ->addTranslation('jkl', 'foo');

        $importer = new ItemTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $this->invokeMethod($importer, 'checkMachineDuplication', $translations, $machine);

        $this->assertTrue($translation1->getIsDuplicatedByMachine());
        $this->assertFalse($translation2->getIsDuplicatedByMachine());
        $this->assertFalse($translation3->getIsDuplicatedByMachine());
    }

    /**
     * Tests the checkMachineDuplication method.
     * @throws ReflectionException
     * @covers ::checkMachineDuplication
     */
    public function testCheckMachineDuplicationWithoutMachine(): void
    {
        $translation1 = new Translation();
        $translation1->setLocale('abc')
                     ->setValue('def')
                     ->setDescription('ghi');

        $translation2 = new Translation();
        $translation2->setLocale('jkl')
                     ->setValue('mno')
                     ->setDescription('pqr');

        $translation3 = new Translation();
        $translation3->setLocale('stu')
                     ->setValue('vwx')
                     ->setDescription('yza');

        $translations = [$translation1, $translation2, $translation3];

        $importer = new ItemTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $this->invokeMethod($importer, 'checkMachineDuplication', $translations, null);

        $this->assertFalse($translation1->getIsDuplicatedByMachine());
        $this->assertFalse($translation2->getIsDuplicatedByMachine());
        $this->assertFalse($translation3->getIsDuplicatedByMachine());
    }
}
