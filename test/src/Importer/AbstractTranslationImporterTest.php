<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Api\Import\Importer\AbstractTranslationImporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the AbstractTranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\AbstractTranslationImporter
 */
class AbstractTranslationImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the createTranslationAggregator method.
     * @throws ReflectionException
     * @covers ::createTranslationAggregator
     */
    public function testCreateTranslationAggregator(): void
    {
        /* @var ModCombination $combination */
        $combination = $this->createMock(ModCombination::class);
        $expectedResult = new TranslationAggregator($combination);

        /* @var AbstractTranslationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->disableOriginalConstructor()
                         ->getMockForAbstractClass();
        $result = $this->invokeMethod($importer, 'createTranslationAggregator', $combination);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getExistingTranslations method.
     * @throws ReflectionException
     * @covers ::getExistingTranslations
     */
    public function testGetExistingTranslations(): void
    {
        /* @var Translation $newTranslation */
        $newTranslation = $this->createMock(Translation::class);
        $newTranslations = [
            'abc' => $newTranslation,
        ];

        /* @var Translation $existingTranslation1 */
        $existingTranslation1 = $this->createMock(Translation::class);
        /* @var Translation $existingTranslation2 */
        $existingTranslation2 = $this->createMock(Translation::class);
        $expectedResult = [
            'abc' => $existingTranslation1,
            'def' => $existingTranslation2,
        ];

        /* @var ModCombination|MockObject $databaseCombination */
        $databaseCombination = $this->getMockBuilder(ModCombination::class)
                                    ->setMethods(['getTranslations'])
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $databaseCombination->expects($this->once())
                            ->method('getTranslations')
                            ->willReturn(new ArrayCollection([$existingTranslation1, $existingTranslation2]));

        /* @var AbstractTranslationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->setMethods(['getIdentifierOfTranslation', 'applyChanges'])
                         ->disableOriginalConstructor()
                         ->getMockForAbstractClass();
        $importer->expects($this->exactly(2))
                 ->method('getIdentifierOfTranslation')
                 ->withConsecutive(
                     [$existingTranslation1],
                     [$existingTranslation2]
                 )
                 ->willReturnOnConsecutiveCalls(
                     'abc',
                     'def'
                 );
        $importer->expects($this->once())
                 ->method('applyChanges')
                 ->with($newTranslation, $existingTranslation1);

        $result = $this->invokeMethod($importer, 'getExistingTranslations', $newTranslations, $databaseCombination);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the applyChanges method.
     * @throws ReflectionException
     * @covers ::applyChanges
     */
    public function testApplyChanges(): void
    {
        $value = 'abc';
        $description = 'def';
        $isDuplicatedByMachine = true;
        $isDuplicatedByRecipe = false;

        /* @var Translation|MockObject $source */
        $source = $this->getMockBuilder(Translation::class)
                       ->setMethods([
                           'getValue',
                           'getDescription',
                           'getIsDuplicatedByMachine',
                           'getIsDuplicatedByRecipe'
                       ])
                       ->disableOriginalConstructor()
                       ->getMock();
        $source->expects($this->once())
               ->method('getValue')
               ->willReturn($value);
        $source->expects($this->once())
               ->method('getDescription')
               ->willReturn($description);
        $source->expects($this->once())
               ->method('getIsDuplicatedByMachine')
               ->willReturn($isDuplicatedByMachine);
        $source->expects($this->once())
               ->method('getIsDuplicatedByRecipe')
               ->willReturn($isDuplicatedByRecipe);

        /* @var Translation|MockObject $destination */
        $destination = $this->getMockBuilder(Translation::class)
                            ->setMethods([
                                'setValue',
                                'setDescription',
                                'setIsDuplicatedByMachine',
                                'setIsDuplicatedByRecipe'
                            ])
                            ->disableOriginalConstructor()
                            ->getMock();
        $destination->expects($this->once())
                    ->method('setValue')
                    ->with($value)
                    ->willReturnSelf();
        $destination->expects($this->once())
                    ->method('setDescription')
                    ->with($description)
                    ->willReturnSelf();
        $destination->expects($this->once())
                    ->method('setIsDuplicatedByMachine')
                    ->with($isDuplicatedByMachine)
                    ->willReturnSelf();
        $destination->expects($this->once())
                    ->method('setIsDuplicatedByRecipe')
                    ->with($isDuplicatedByRecipe)
                    ->willReturnSelf();

        /* @var AbstractTranslationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->disableOriginalConstructor()
                         ->getMockForAbstractClass();

        $this->invokeMethod($importer, 'applyChanges', $source, $destination);
    }

    /**
     * Tests the getIdentifierOfTranslation method.
     * @throws ReflectionException
     * @covers ::getIdentifierOfTranslation
     */
    public function testGetIdentifierOfTranslation(): void
    {
        $translation = new Translation($this->createMock(ModCombination::class), 'abc', 'def', 'ghi');
        $expectedResult = 'abc|def|ghi';

        /* @var AbstractTranslationImporter|MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->disableOriginalConstructor()
                         ->getMockForAbstractClass();

        $result = $this->invokeMethod($importer, 'getIdentifierOfTranslation', $translation);

        $this->assertSame($expectedResult, $result);
    }
}
