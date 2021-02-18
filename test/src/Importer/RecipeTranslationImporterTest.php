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
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Recipe as ExportRecipe;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the RecipeTranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\RecipeTranslationImporter
 */
class RecipeTranslationImporterTest extends TestCase
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
     * @return RecipeTranslationImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): RecipeTranslationImporter
    {
        return $this->getMockBuilder(RecipeTranslationImporter::class)
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
        $recipe1 = new ExportRecipe();
        $recipe1->mode = RecipeMode::NORMAL;

        $recipe2 = new ExportRecipe();
        $recipe2->mode = RecipeMode::EXPENSIVE;

        $recipe3 = new ExportRecipe();
        $recipe3->mode = RecipeMode::NORMAL;


        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getRecipes()->add($recipe1)
                                 ->add($recipe2)
                                 ->add($recipe3);

        $importer = $this->createInstance();
        $result = $this->invokeMethod($importer, 'getExportEntities', $exportData);

        $this->assertEquals([$recipe1, $recipe3], iterator_to_array($result));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateTranslationsForEntity(): void
    {
        $name = 'abc';
        $exportData = $this->createMock(ExportData::class);

        $recipe = new ExportRecipe();
        $recipe->name = $name;

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

        $importer = $this->createInstance([
                             'createTranslationsFromLocalisedStrings',
                             'findItem',
                             'filterDuplicatesToItems',
                         ]);
        $importer->expects($this->once())
                 ->method('createTranslationsFromLocalisedStrings')
                 ->with(
                     $this->identicalTo(EntityType::RECIPE),
                     $this->identicalTo('abc'),
                     $this->identicalTo($recipe->labels),
                     $this->identicalTo($recipe->descriptions),
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
