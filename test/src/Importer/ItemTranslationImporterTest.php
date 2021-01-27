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
use FactorioItemBrowser\ExportData\Entity\Item as ExportItem;
use FactorioItemBrowser\ExportData\Entity\Machine;
use FactorioItemBrowser\ExportData\Entity\Recipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the ItemTranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\ItemTranslationImporter
 */
class ItemTranslationImporterTest extends TestCase
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
     * @return ItemTranslationImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): ItemTranslationImporter
    {
        return $this->getMockBuilder(ItemTranslationImporter::class)
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
    public function testGetExportEntities(): void
    {
        $item1 = $this->createMock(ExportItem::class);
        $item2 = $this->createMock(ExportItem::class);
        $item3 = $this->createMock(ExportItem::class);

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getItems()->add($item1)
                               ->add($item2)
                               ->add($item3);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getExportEntities', $exportData);

        $this->assertEquals([$item1, $item2, $item3], iterator_to_array($result));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateTranslationsForEntity(): void
    {
        $exportData = $this->createMock(ExportData::class);

        $item = new ExportItem();
        $item->type = 'abc';
        $item->name = 'def';

        $recipe = $this->createMock(Recipe::class);
        $machine = $this->createMock(Machine::class);

        $translations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];

        $instance = $this->createInstance([
                             'createTranslationsFromLocalisedStrings',
                             'findRecipe',
                             'checkRecipeDuplication',
                             'findMachine',
                             'checkMachineDuplication',
                         ]);
        $instance->expects($this->once())
                 ->method('createTranslationsFromLocalisedStrings')
                 ->with(
                     $this->identicalTo('abc'),
                     $this->identicalTo('def'),
                     $this->identicalTo($item->labels),
                     $this->identicalTo($item->descriptions),
                 )
                 ->willReturn($translations);
        $instance->expects($this->once())
                 ->method('findRecipe')
                 ->with($this->identicalTo($exportData), $this->identicalTo('def'))
                 ->willReturn($recipe);
        $instance->expects($this->once())
                 ->method('checkRecipeDuplication')
                 ->with($this->identicalTo($translations), $this->identicalTo($recipe));
        $instance->expects($this->once())
                 ->method('findMachine')
                 ->with($this->identicalTo($exportData), $this->identicalTo('def'))
                 ->willReturn($machine);
        $instance->expects($this->once())
                 ->method('checkMachineDuplication')
                 ->with($this->identicalTo($translations), $this->identicalTo($machine));

        $result = $this->invokeMethod($instance, 'createTranslationsForEntity', $exportData, $item);

        $this->assertSame($translations, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFindRecipe(): void
    {
        $name = 'def';

        $recipe1 = new Recipe();
        $recipe1->name = 'abc';
        $recipe1->mode = RecipeMode::NORMAL;

        $recipe2 = new Recipe();
        $recipe2->name = 'def';
        $recipe2->mode = RecipeMode::EXPENSIVE;

        $recipe3 = new Recipe();
        $recipe3->name = 'def';
        $recipe3->mode = RecipeMode::NORMAL;

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getRecipes()->add($recipe1)
                                 ->add($recipe2)
                                 ->add($recipe3);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'findRecipe', $exportData, $name);

        $this->assertSame($recipe3, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFindRecipeWithoutMatch(): void
    {
        $name = 'foo';

        $recipe1 = new Recipe();
        $recipe1->name = 'abc';

        $recipe2 = new Recipe();
        $recipe2->name = 'def';

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getRecipes()->add($recipe1)
                                 ->add($recipe2);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'findRecipe', $exportData, $name);

        $this->assertNull($result);
    }

    /**
     * @throws ReflectionException
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
        $recipe->labels->set('abc', 'def');
        $recipe->labels->set('jkl', 'mno');
        $recipe->descriptions->set('abc', 'ghi');
        $recipe->descriptions->set('jkl', 'foo');

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'checkRecipeDuplication', $translations, $recipe);

        $this->assertTrue($translation1->getIsDuplicatedByRecipe());
        $this->assertFalse($translation2->getIsDuplicatedByRecipe());
        $this->assertFalse($translation3->getIsDuplicatedByRecipe());
    }

    /**
     * @throws ReflectionException
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

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'checkRecipeDuplication', $translations, null);

        $this->assertFalse($translation1->getIsDuplicatedByRecipe());
        $this->assertFalse($translation2->getIsDuplicatedByRecipe());
        $this->assertFalse($translation3->getIsDuplicatedByRecipe());
    }

    /**
     * @throws ReflectionException
     */
    public function testFindMachine(): void
    {
        $name = 'def';

        $machine1 = new Machine();
        $machine1->name = 'abc';

        $machine2 = new Machine();
        $machine2->name = 'def';

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getMachines()->add($machine1)
                                  ->add($machine2);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'findMachine', $exportData, $name);

        $this->assertSame($machine2, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFindMachineWithoutMatch(): void
    {
        $name = 'foo';

        $machine1 = new Machine();
        $machine1->name = 'abc';

        $machine2 = new Machine();
        $machine2->name = 'def';

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getMachines()->add($machine1)
                                  ->add($machine2);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'findMachine', $exportData, $name);

        $this->assertNull($result);
    }

    /**
     * @throws ReflectionException
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
        $machine->labels->set('abc', 'def');
        $machine->labels->set('jkl', 'mno');
        $machine->descriptions->set('abc', 'ghi');
        $machine->descriptions->set('jkl', 'foo');

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'checkMachineDuplication', $translations, $machine);

        $this->assertTrue($translation1->getIsDuplicatedByMachine());
        $this->assertFalse($translation2->getIsDuplicatedByMachine());
        $this->assertFalse($translation3->getIsDuplicatedByMachine());
    }

    /**
     * @throws ReflectionException
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

        $instance = $this->createInstance();
        $this->invokeMethod($instance, 'checkMachineDuplication', $translations, null);

        $this->assertFalse($translation1->getIsDuplicatedByMachine());
        $this->assertFalse($translation2->getIsDuplicatedByMachine());
        $this->assertFalse($translation3->getIsDuplicatedByMachine());
    }
}
