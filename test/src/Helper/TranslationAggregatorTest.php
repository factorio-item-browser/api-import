<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Helper;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\ModCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\ExportData\Entity\LocalisedString;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the TranslationAggregator class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Helper\TranslationAggregator
 */
class TranslationAggregatorTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var ModCombination $combination */
        $combination = $this->createMock(ModCombination::class);

        $aggregator = new TranslationAggregator($combination);

        $this->assertSame($combination, $this->extractProperty($aggregator, 'combination'));
    }

    /**
     * Tests the addTranslation method.
     * @throws ReflectionException
     * @covers ::addTranslation
     */
    public function testAddTranslation(): void
    {
        /* @var Translation $translation1 */
        $translation1 = $this->createMock(Translation::class);
        /* @var Translation $translation2 */
        $translation2 = $this->createMock(Translation::class);
        $translations = [
            'abc' => $translation1,
            'def' => $translation2,
        ];

        /* @var ModCombination $combination */
        $combination = $this->createMock(ModCombination::class);

        $newTranslation = new Translation($combination, 'ghi', 'jkl', 'mno');
        $expectedTranslations = [
            'abc' => $translation1,
            'def' => $translation2,
            'ghi|jkl|mno' => $newTranslation,
        ];

        $aggregator = new TranslationAggregator($combination);
        $this->injectProperty($aggregator, 'translations', $translations);

        $aggregator->addTranslation($newTranslation);
        $this->assertSame($expectedTranslations, $this->extractProperty($aggregator, 'translations'));
    }

    /**
     * Tests the applyLocalisedStringToValue method.
     * @throws ReflectionException
     * @covers ::applyLocalisedStringToValue
     */
    public function testApplyLocalisedStringToValue(): void
    {
        $localisedString = new LocalisedString();
        $localisedString->setTranslation('en', 'abc')
                        ->setTranslation('de', 'def');
        $type = 'ghi';
        $name = 'jkl';

        /* @var Translation|MockObject $translation1 */
        $translation1 = $this->getMockBuilder(Translation::class)
                             ->setMethods(['setValue'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $translation1->expects($this->once())
                     ->method('setValue')
                     ->with('abc');

        /* @var Translation|MockObject $translation2 */
        $translation2 = $this->getMockBuilder(Translation::class)
                             ->setMethods(['setValue'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $translation2->expects($this->once())
                     ->method('setValue')
                     ->with('def');

        /* @var TranslationAggregator|MockObject $aggregator */
        $aggregator = $this->getMockBuilder(TranslationAggregator::class)
                           ->setMethods(['getTranslation'])
                           ->disableOriginalConstructor()
                           ->getMock();
        $aggregator->expects($this->exactly(2))
                   ->method('getTranslation')
                   ->withConsecutive(
                       ['en', 'ghi', 'jkl'],
                       ['de', 'ghi', 'jkl']
                   )
                   ->willReturnOnConsecutiveCalls(
                       $translation1,
                       $translation2
                   );

        $aggregator->applyLocalisedStringToValue($localisedString, $type, $name);
    }
    
    /**
     * Tests the applyLocalisedStringToDescription method.
     * @throws ReflectionException
     * @covers ::applyLocalisedStringToDescription
     */
    public function testApplyLocalisedStringToDescription(): void
    {
        $localisedString = new LocalisedString();
        $localisedString->setTranslation('en', 'abc')
                        ->setTranslation('de', 'def');
        $type = 'ghi';
        $name = 'jkl';

        /* @var Translation|MockObject $translation1 */
        $translation1 = $this->getMockBuilder(Translation::class)
                             ->setMethods(['setDescription'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $translation1->expects($this->once())
                     ->method('setDescription')
                     ->with('abc');

        /* @var Translation|MockObject $translation2 */
        $translation2 = $this->getMockBuilder(Translation::class)
                             ->setMethods(['setDescription'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $translation2->expects($this->once())
                     ->method('setDescription')
                     ->with('def');

        /* @var TranslationAggregator|MockObject $aggregator */
        $aggregator = $this->getMockBuilder(TranslationAggregator::class)
                           ->setMethods(['getTranslation'])
                           ->disableOriginalConstructor()
                           ->getMock();
        $aggregator->expects($this->exactly(2))
                   ->method('getTranslation')
                   ->withConsecutive(
                       ['en', 'ghi', 'jkl'],
                       ['de', 'ghi', 'jkl']
                   )
                   ->willReturnOnConsecutiveCalls(
                       $translation1,
                       $translation2
                   );

        $aggregator->applyLocalisedStringToDescription($localisedString, $type, $name);
    }

    /**
     * Tests the applyLocalisedStringToDuplicationFlags method.
     * @throws ReflectionException
     * @covers ::applyLocalisedStringToDuplicationFlags
     */
    public function testApplyLocalisedStringToDuplicationFlags(): void
    {
        $localisedString = new LocalisedString();
        $localisedString->setTranslation('en', 'abc')
                        ->setTranslation('de', 'def');

        $type = 'ghi';
        $name = 'jkl';
        $isDuplicatedByMachine = true;
        $isDuplicatedByRecipe = false;

        /* @var Translation|MockObject $translation1 */
        $translation1 = $this->getMockBuilder(Translation::class)
                             ->setMethods(['setIsDuplicatedByMachine', 'setIsDuplicatedByRecipe'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $translation1->expects($this->once())
                     ->method('setIsDuplicatedByMachine')
                     ->with($isDuplicatedByMachine)
                     ->willReturnSelf();
        $translation1->expects($this->once())
                     ->method('setIsDuplicatedByRecipe')
                     ->with($isDuplicatedByRecipe);

        /* @var Translation|MockObject $translation2 */
        $translation2 = $this->getMockBuilder(Translation::class)
                             ->setMethods(['setIsDuplicatedByMachine', 'setIsDuplicatedByRecipe'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $translation2->expects($this->once())
                     ->method('setIsDuplicatedByMachine')
                     ->with($isDuplicatedByMachine)
                     ->willReturnSelf();
        $translation2->expects($this->once())
                     ->method('setIsDuplicatedByRecipe')
                     ->with($isDuplicatedByRecipe);

        /* @var TranslationAggregator|MockObject $aggregator */
        $aggregator = $this->getMockBuilder(TranslationAggregator::class)
                           ->setMethods(['getTranslation'])
                           ->disableOriginalConstructor()
                           ->getMock();
        $aggregator->expects($this->exactly(2))
                   ->method('getTranslation')
                   ->withConsecutive(
                       ['en', 'ghi', 'jkl'],
                       ['de', 'ghi', 'jkl']
                   )
                   ->willReturnOnConsecutiveCalls(
                       $translation1,
                       $translation2
                   );

        $aggregator->applyLocalisedStringToDuplicationFlags(
            $localisedString,
            $type,
            $name,
            $isDuplicatedByMachine,
            $isDuplicatedByRecipe
        );
    }

    /**
     * Provides the data for the getTranslation test.
     * @return array
     * @throws ReflectionException
     */
    public function provideGetTranslation(): array
    {
        /* @var ModCombination $combination */
        $combination = $this->createMock(ModCombination::class);

        $translation1 = new Translation($combination, 'abc', 'def', 'ghi');
        $translation2 = new Translation($combination, 'jkl', 'mno', 'pqr');

        return [
            [
                ['abc|def|ghi' => $translation1, 'jkl|mno|pqr' => $translation2],
                'abc',
                'def',
                'ghi',
                null,
                $translation1,
                ['abc|def|ghi' => $translation1, 'jkl|mno|pqr' => $translation2],
            ],
            [
                ['abc|def|ghi' => $translation1],
                'jkl',
                'mno',
                'pqr',
                $translation2,
                $translation2,
                ['abc|def|ghi' => $translation1, 'jkl|mno|pqr' => $translation2],
            ]
        ];
    }

    /**
     * Tests the getTranslation method.
     * @param array|Translation[] $translations
     * @param string $locale
     * @param string $type
     * @param string $name
     * @param Translation|null $resultCreate
     * @param Translation $expectedResult
     * @param array|Translation[] $expectedTranslations
     * @throws ReflectionException
     * @covers ::createTranslation
     * @dataProvider provideGetTranslation
     */
    public function testGetTranslation(
        array $translations,
        string $locale,
        string $type,
        string $name,
        ?Translation $resultCreate,
        Translation $expectedResult,
        array $expectedTranslations
    ): void {
        /* @var TranslationAggregator|MockObject $aggregator */
        $aggregator = $this->getMockBuilder(TranslationAggregator::class)
                           ->setMethods(['createTranslation'])
                           ->disableOriginalConstructor()
                           ->getMock();
        if ($resultCreate === null) {
            $aggregator->expects($this->never())
                       ->method('createTranslation');
        } else {
            $aggregator->expects($this->once())
                       ->method('createTranslation')
                       ->with($locale, $type, $name)
                       ->willReturn($resultCreate);
        }
        $this->injectProperty($aggregator, 'translations', $translations);

        $result = $this->invokeMethod($aggregator, 'getTranslation', $locale, $type, $name);

        $this->assertSame($expectedResult, $result);
        $this->assertSame($expectedTranslations, $this->extractProperty($aggregator, 'translations'));
    }

    /**
     * Tests the createTranslation method.
     * @throws ReflectionException
     * @covers ::createTranslation
     */
    public function testCreateTranslation(): void
    {
        /* @var ModCombination $combination */
        $combination = $this->createMock(ModCombination::class);
        $locale = 'abc';
        $type = 'def';
        $name = 'ghi';
        $expectedResult = new Translation($combination, $locale, $type, $name);

        $aggregator = new TranslationAggregator($combination);
        $result = $this->invokeMethod($aggregator, 'createTranslation', $locale, $type, $name);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the getAggregatedTranslations method.
     * @throws ReflectionException
     * @covers ::getAggregatedTranslations
     */
    public function testGetAggregatedTranslations(): void
    {
        $translations = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];

        /* @var ModCombination $combination */
        $combination = $this->createMock(ModCombination::class);

        $aggregator = new TranslationAggregator($combination);
        $this->injectProperty($aggregator, 'translations', $translations);

        $result = $aggregator->getAggregatedTranslations();

        $this->assertEquals($translations, $result);
    }

    /**
     * Tests the getIdentifier method.
     * @covers ::getIdentifier
     */
    public function testGetIdentifier(): void
    {
        $locale = 'abc';
        $type = 'def';
        $name = 'ghi';
        $expectedResult = 'abc|def|ghi';

        $result = TranslationAggregator::getIdentifier($locale, $type, $name);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getIdentifierOfTranslation method.
     * @covers ::getIdentifierOfTranslation
     */
    public function testGetIdentifierOfTranslation(): void
    {
        $translation = new Translation($this->createMock(ModCombination::class), 'abc', 'def', 'ghi');
        $expectedResult = 'abc|def|ghi';

        $result = TranslationAggregator::getIdentifierOfTranslation($translation);

        $this->assertSame($expectedResult, $result);
    }
}
