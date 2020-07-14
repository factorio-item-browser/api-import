<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\RecipeTranslationImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\Common\Constant\RecipeMode;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the RecipeTranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\RecipeTranslationImporter
 */
class RecipeTranslationImporterTest extends TestCase
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
        $recipe1 = new ExportRecipe();
        $recipe1->setMode(RecipeMode::NORMAL);

        $recipe2 = new ExportRecipe();
        $recipe2->setMode(RecipeMode::EXPENSIVE);

        $recipe3 = new ExportRecipe();
        $recipe3->setMode(RecipeMode::NORMAL);

        $combination = new ExportCombination();
        $combination->setRecipes([$recipe1, $recipe2, $recipe3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        $importer = new RecipeTranslationImporter(
            $this->entityManager,
            $this->idCalculator,
            $this->repository,
            $this->validator,
        );
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals([$recipe1, $recipe3], iterator_to_array($result));
    }
    
    /**
     * Tests the createTranslationsForEntity method.
     * @throws ReflectionException
     * @covers ::createTranslationsForEntity
     */
    public function testCreateTranslationsForEntity(): void
    {
        $name = 'abc';
        $exportData = $this->createMock(ExportData::class);
        
        $recipe = new ExportRecipe();
        $recipe->setName($name);

        $translations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];
        $filteredTranslations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];

        $item = $this->createMock(Item::class);
        $expectedItems = [$item];

        $importer = $this->getMockBuilder(RecipeTranslationImporter::class)
                         ->onlyMethods([
                             'createTranslationsFromLocalisedStrings',
                             'findItem',
                             'filterDuplicatesToItems',
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
                     $this->identicalTo(EntityType::RECIPE),
                     $this->identicalTo('abc'),
                     $this->identicalTo($recipe->getLabels()),
                     $this->identicalTo($recipe->getDescriptions()),
                 )
                 ->willReturn($translations);
        $importer->expects($this->exactly(2))
                 ->method('findItem')
                 ->withConsecutive(
                     [
                         $this->identicalTo($exportData),
                         $this->identicalTo(EntityType::ITEM),
                         $this->identicalTo($name),
                     ],
                     [
                         $this->identicalTo($exportData),
                         $this->identicalTo(EntityType::FLUID),
                         $this->identicalTo($name),
                     ],
                 )
                 ->willReturnOnConsecutiveCalls(
                     $item,
                     null,
                 );
        $importer->expects($this->once())
                 ->method('filterDuplicatesToItems')
                 ->with($this->identicalTo($translations), $this->identicalTo($expectedItems))
                 ->willReturn($filteredTranslations);


        $result = $this->invokeMethod($importer, 'createTranslationsForEntity', $exportData, $recipe);

        $this->assertSame($filteredTranslations, $result);
    }
}
