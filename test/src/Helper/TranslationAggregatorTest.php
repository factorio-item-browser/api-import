<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Helper;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Import\Helper\TranslationAggregator;
use FactorioItemBrowser\Common\Constant\EntityType;
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
     * Tests the add method.
     * @covers ::add
     */
    public function testAdd(): void
    {
        $type = 'abc';
        $name = 'def';

        $values = new LocalisedString();
        $values->setTranslations(['ghi' => 'jkl', 'mno' => 'pqr']);

        $descriptions = new LocalisedString();
        $descriptions->setTranslations(['ghi' => 'stu', 'vwx' => 'yza']);

        /* @var Translation&MockObject $translation1 */
        $translation1 = $this->createMock(Translation::class);
        $translation1->expects($this->once())
                     ->method('setValue')
                     ->with($this->identicalTo('jkl'));

        /* @var Translation&MockObject $translation2 */
        $translation2 = $this->createMock(Translation::class);
        $translation2->expects($this->once())
                     ->method('setValue')
                     ->with($this->identicalTo('pqr'));

        /* @var Translation&MockObject $translation3 */
        $translation3 = $this->createMock(Translation::class);
        $translation3->expects($this->once())
                     ->method('setDescription')
                     ->with($this->identicalTo('stu'));

        /* @var Translation&MockObject $translation4 */
        $translation4 = $this->createMock(Translation::class);
        $translation4->expects($this->once())
                     ->method('setDescription')
                     ->with($this->identicalTo('yza'));

        /* @var TranslationAggregator&MockObject $helper */
        $helper = $this->getMockBuilder(TranslationAggregator::class)
                       ->onlyMethods(['createTranslation'])
                       ->getMock();
        $helper->expects($this->exactly(4))
               ->method('createTranslation')
               ->withConsecutive(
                   [$this->identicalTo('ghi'), $this->identicalTo($type), $this->identicalTo($name)],
                   [$this->identicalTo('mno'), $this->identicalTo($type), $this->identicalTo($name)],
                   [$this->identicalTo('ghi'), $this->identicalTo($type), $this->identicalTo($name)],
                   [$this->identicalTo('vwx'), $this->identicalTo($type), $this->identicalTo($name)]
               )
               ->willReturnOnConsecutiveCalls(
                   $translation1,
                   $translation2,
                   $translation3,
                   $translation4
               );

        $helper->add($type, $name, $values, $descriptions);
    }

    /**
     * Tests the createTranslation method.
     * @throws ReflectionException
     * @covers ::createTranslation
     */
    public function testCreateTranslation(): void
    {
        $locale = 'abc';
        $type = 'def';
        $name = 'ghi';

        $expectedTranslation = new Translation();
        $expectedTranslation->setLocale('abc')
                            ->setType('def')
                            ->setName('ghi');

        $expectedTranslations = [
            'abc' => [
                'def' => [
                    'ghi' => $expectedTranslation,
                ],
            ],
        ];

        $helper = new TranslationAggregator();
        $result = $this->invokeMethod($helper, 'createTranslation', $locale, $type, $name);

        $this->assertEquals($expectedTranslation, $result);
        $this->assertEquals($expectedTranslations, $this->extractProperty($helper, 'translations'));
    }

    /**
     * Tests the createTranslation method.
     * @throws ReflectionException
     * @covers ::createTranslation
     */
    public function testCreateTranslationWithExistingTranslation(): void
    {
        $locale = 'abc';
        $type = 'def';
        $name = 'ghi';

        /* @var Translation&MockObject $translation */
        $translation = $this->createMock(Translation::class);

        $translations = [
            'abc' => [
                'def' => [
                    'ghi' => $translation,
                ],
            ],
        ];

        $helper = new TranslationAggregator();
        $this->injectProperty($helper, 'translations', $translations);

        $result = $this->invokeMethod($helper, 'createTranslation', $locale, $type, $name);

        $this->assertSame($translation, $result);
    }

    /**
     * Tests the optimize method.
     * @covers ::optimize
     */
    public function testOptimize(): void
    {
        /* @var Translation&MockObject $translation1 */
        $translation1 = $this->createMock(Translation::class);
        $translation1->expects($this->once())
                     ->method('setIsDuplicatedByMachine')
                     ->with($this->isTrue());
        $translation1->expects($this->never())
                     ->method('setIsDuplicatedByRecipe');

        /* @var Translation&MockObject $translation2 */
        $translation2 = $this->createMock(Translation::class);
        $translation2->expects($this->once())
                     ->method('setIsDuplicatedByRecipe')
                     ->with($this->isTrue());
        $translation2->expects($this->never())
                     ->method('setIsDuplicatedByMachine');

        /* @var TranslationAggregator&MockObject $helper */
        $helper = $this->getMockBuilder(TranslationAggregator::class)
                       ->onlyMethods(['optimizeType'])
                       ->getMock();
        $helper->expects($this->exactly(2))
               ->method('optimizeType')
               ->withConsecutive(
                   [
                       $this->identicalTo(EntityType::MACHINE),
                       $this->callback(function ($callback) use ($translation1): bool {
                           $this->assertIsCallable($callback);
                           $callback($translation1);
                           return true;
                       }),
                   ],
                   [
                       $this->identicalTo(EntityType::RECIPE),
                       $this->callback(function ($callback) use ($translation2): bool {
                           $this->assertIsCallable($callback);
                           $callback($translation2);
                           return true;
                       }),
                   ]
               );

        $result = $helper->optimize();
        $this->assertSame($helper, $result);
    }

    /**
     * Tests the optimizeType method.
     * @throws ReflectionException
     * @covers ::optimizeType
     */
    public function testOptimizeType(): void
    {
        /* @var Translation&MockObject $translation1 */
        $translation1 = $this->createMock(Translation::class);
        /* @var Translation&MockObject $translation2 */
        $translation2 = $this->createMock(Translation::class);
        /* @var Translation&MockObject $duplicatingTranslation1 */
        $duplicatingTranslation1 = $this->createMock(Translation::class);

        $type = 'def';
        $translations = [
            'abc' => [
                'def' => [
                    'ghi' => $translation1,
                    'jkl' => $translation2,
                ],
            ],
        ];
        $expectedTranslations = [
            'abc' => [
                'def' => [
                    'jkl' => $translation2,
                ],
            ],
        ];

        $callback = function (Translation $translation) use ($duplicatingTranslation1): void {
            $this->assertSame($duplicatingTranslation1, $translation);
        };
        
        /* @var TranslationAggregator&MockObject $helper */
        $helper = $this->getMockBuilder(TranslationAggregator::class)
                       ->onlyMethods(['getDuplicatingTranslation'])
                       ->getMock();
        $helper->expects($this->exactly(2))
               ->method('getDuplicatingTranslation')
               ->withConsecutive(
                   [$this->identicalTo($translation1)],
                   [$this->identicalTo($translation2)]
               )
               ->willReturnOnConsecutiveCalls(
                   $duplicatingTranslation1,
                   null
               );
        $this->injectProperty($helper, 'translations', $translations);

        $this->invokeMethod($helper, 'optimizeType', $type, $callback);

        $this->assertSame($expectedTranslations, $this->extractProperty($helper, 'translations'));
    }

    /**
     * Provides the data for the getDuplicatingTranslation test.
     * @return array<mixed>
     */
    public function provideGetDuplicatingTranslation(): array
    {
        $item1 = new Translation();
        $item1->setValue('abc')
              ->setDescription('def');
        $item2 = new Translation();
        $item2->setValue('ghi')
              ->setDescription('jkl');
        $fluid1 = new Translation();
        $fluid1->setValue('mno')
               ->setDescription('pqr');
        $fluid2 = new Translation();
        $fluid2->setValue('stu')
               ->setDescription('vwx');

        $translations = [
            'foo' => [
                EntityType::ITEM => [
                    'item1' => $item1,
                    'item2' => $item2,
                    'fluid1' => $item1,
                ],
                EntityType::FLUID => [
                    'fluid1' => $fluid1,
                    'fluid2' => $fluid2,
                ],
            ],
        ];

        // Translation is duplicated by $item1
        $translation1 = new Translation();
        $translation1->setLocale('foo')
                     ->setType(EntityType::RECIPE)
                     ->setName('item1')
                     ->setValue('abc')
                     ->setDescription('def');

        // Translation is NOT duplicated by $item2
        $translation2 = new Translation();
        $translation2->setLocale('foo')
                     ->setType(EntityType::RECIPE)
                     ->setName('item2')
                     ->setValue('abc')
                     ->setDescription('def');

        // Translation is duplicated by $fluid1
        $translation3 = new Translation();
        $translation3->setLocale('foo')
                     ->setType(EntityType::RECIPE)
                     ->setName('fluid1')
                     ->setValue('mno')
                     ->setDescription('pqr');

        // Translation is NOT duplicated by $item1, but by $fluid1
        $translation3 = new Translation();
        $translation3->setLocale('foo')
                     ->setType(EntityType::RECIPE)
                     ->setName('fluid1')
                     ->setValue('mno')
                     ->setDescription('pqr');

        // Translation is NOT duplicated by $fluid2
        $translation4 = new Translation();
        $translation4->setLocale('foo')
                     ->setType(EntityType::RECIPE)
                     ->setName('fluid2')
                     ->setValue('mno')
                     ->setDescription('pqr');

        // Translation does not have a description and is thus duplicated by $item1
        $translation5 = new Translation();
        $translation5->setLocale('foo')
                     ->setType(EntityType::RECIPE)
                     ->setName('item1')
                     ->setValue('abc')
                     ->setDescription('');

        return [
            [$translations, $translation1, $item1],
            [$translations, $translation2, null],
            [$translations, $translation3, $fluid1],
            [$translations, $translation4, null],
            [$translations, $translation5, $item1],
            [[], $translation1, null],
        ];
    }

    /**
     * Tests the getDuplicatingTranslation method.
     * @param array|Translation[][][] $translations
     * @param Translation $translation
     * @param Translation|null $expectedResult
     * @throws ReflectionException
     * @covers ::getDuplicatingTranslation
     * @dataProvider provideGetDuplicatingTranslation
     */
    public function testGetDuplicatingTranslation(
        array $translations,
        Translation $translation,
        ?Translation $expectedResult
    ): void {
        $helper = new TranslationAggregator();
        $this->injectProperty($helper, 'translations', $translations);

        $result = $this->invokeMethod($helper, 'getDuplicatingTranslation', $translation);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getTranslations method.
     * @throws ReflectionException
     * @covers ::getTranslations
     */
    public function testGetTranslations(): void
    {
        /* @var Translation&MockObject $translation1 */
        $translation1 = $this->createMock(Translation::class);
        /* @var Translation&MockObject $translation2 */
        $translation2 = $this->createMock(Translation::class);
        /* @var Translation&MockObject $translation3 */
        $translation3 = $this->createMock(Translation::class);
        /* @var Translation&MockObject $translation4 */
        $translation4 = $this->createMock(Translation::class);
        /* @var Translation&MockObject $translation5 */
        $translation5 = $this->createMock(Translation::class);
        /* @var Translation&MockObject $translation6 */
        $translation6 = $this->createMock(Translation::class);

        $translations = [
            'abc' => [
                'def' => [
                    'ghi' => $translation1,
                    'jkl' => $translation2,
                ],
                'mno' => [
                    'pqr' => $translation3,
                    'stu' => $translation4,
                ],
            ],
            'vwx' => [
                'yza' => [
                    'bcd' => $translation5,
                    'efg' => $translation6,
                ],
            ],
        ];
        $expectedResult = [
            $translation1,
            $translation2,
            $translation3,
            $translation4,
            $translation5,
            $translation6,
        ];

        $helper = new TranslationAggregator();
        $this->injectProperty($helper, 'translations', $translations);

        $result = $helper->getTranslations();

        $this->assertSame($expectedResult, $result);
    }
}
