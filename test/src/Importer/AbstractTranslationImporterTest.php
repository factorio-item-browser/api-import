<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\AbstractTranslationImporter;
use FactorioItemBrowser\ExportData\Collection\TranslationDictionary;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the AbstractTranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\AbstractTranslationImporter
 */
class AbstractTranslationImporterTest extends TestCase
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
     * @return AbstractTranslationImporter<mixed>&MockObject
     */
    private function createInstance(array $mockedMethods = []): AbstractTranslationImporter
    {
        return $this->getMockBuilder(AbstractTranslationImporter::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->setConstructorArgs([
                        $this->entityManager,
                        $this->idCalculator,
                        $this->repository,
                        $this->validator,
                    ])
                    ->getMockForAbstractClass();
    }

    public function testPrepare(): void
    {
        $combination = $this->createMock(DatabaseCombination::class);

        $instance = $this->createInstance();
        $instance->prepare($combination);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws DBALException
     */
    public function testImport(): void
    {
        $exportData = $this->createMock(ExportData::class);
        $combinationId = $this->createMock(UuidInterface::class);
        $offset = 1337;
        $limit = 42;

        $entities = [
            $this->createMock(Translation::class),
            $this->createMock(Translation::class),
        ];

        $combination = new DatabaseCombination();
        $combination->setId($combinationId);

        $this->repository->expects($this->once())
                         ->method('persistTranslationsToCombination')
                         ->with($this->identicalTo($combinationId), $this->identicalTo($entities));

        $instance = $this->createInstance(['createTranslations']);
        $instance->expects($this->once())
                 ->method('createTranslations')
                 ->with($this->identicalTo($exportData), $this->identicalTo($offset), $this->identicalTo($limit))
                 ->willReturn($entities);

        $instance->import($combination, $exportData, $offset, $limit);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateTranslations(): void
    {
        $exportData = $this->createMock(ExportData::class);
        $offset = 1337;
        $limit = 42;

        $entity1 = ['abc', 'def', 'ghi'];
        $entity2 = ['jkl', 'mno', 'pqr'];
        $entities = [$entity1, $entity2];

        $translationId1 = $this->createMock(UuidInterface::class);
        $translationId2 = $this->createMock(UuidInterface::class);
        $translationId3 = $this->createMock(UuidInterface::class);
        $translationId4 = $this->createMock(UuidInterface::class);

        $translation1 = $this->createMock(Translation::class);
        $translation1->expects($this->once())
                     ->method('setId')
                     ->with($this->identicalTo($translationId1));

        $translation2 = $this->createMock(Translation::class);
        $translation2->expects($this->once())
                     ->method('setId')
                     ->with($this->identicalTo($translationId2));

        $translation3 = $this->createMock(Translation::class);
        $translation3->expects($this->once())
                     ->method('setId')
                     ->with($this->identicalTo($translationId3));

        $translation4 = $this->createMock(Translation::class);
        $translation4->expects($this->once())
                     ->method('setId')
                     ->with($this->identicalTo($translationId4));

        $expectedResult = [$translation1, $translation2, $translation3, $translation4];

        $this->validator->expects($this->exactly(4))
                        ->method('validateTranslation')
                        ->withConsecutive(
                            [$this->identicalTo($translation1)],
                            [$this->identicalTo($translation2)],
                            [$this->identicalTo($translation3)],
                            [$this->identicalTo($translation4)],
                        );

        $this->idCalculator->expects($this->exactly(4))
                           ->method('calculateIdOfTranslation')
                           ->withConsecutive(
                               [$this->identicalTo($translation1)],
                               [$this->identicalTo($translation2)],
                               [$this->identicalTo($translation3)],
                               [$this->identicalTo($translation4)]
                           )
                           ->willReturnOnConsecutiveCalls(
                               $translationId1,
                               $translationId2,
                               $translationId3,
                               $translationId4,
                           );

        $instance = $this->createInstance(['getChunkedExportEntities', 'createTranslationsForEntity']);
        $instance->expects($this->once())
                 ->method('getChunkedExportEntities')
                 ->with($this->identicalTo($exportData), $this->identicalTo($offset), $this->identicalTo($limit))
                 ->willReturn($entities);
        $instance->expects($this->exactly(2))
                 ->method('createTranslationsForEntity')
                 ->withConsecutive(
                     [$this->identicalTo($exportData), $this->identicalTo($entity1)],
                     [$this->identicalTo($exportData), $this->identicalTo($entity2)],
                 )
                 ->willReturnOnConsecutiveCalls(
                     [$translation1, $translation2],
                     [$translation3, $translation4],
                 );

        $result = $this->invokeMethod($instance, 'createTranslations', $exportData, $offset, $limit);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateTranslationsFromLocalisedStrings(): void
    {
        $type = 'abc';
        $name = 'def';

        $values = new TranslationDictionary();
        $values->set('ghi', 'jkl');
        $values->set('mno', 'pqr');

        $description = new TranslationDictionary();
        $description->set('ghi', 'stu');
        $description->set('vwx', 'yza');

        $translationId1 = $this->createMock(UuidInterface::class);
        $translationId2 = $this->createMock(UuidInterface::class);
        $translationId3 = $this->createMock(UuidInterface::class);

        $translation1 = $this->createMock(Translation::class);
        $translation1->expects($this->once())
                     ->method('setValue')
                     ->with($this->identicalTo('jkl'));
        $translation1->expects($this->once())
                     ->method('setDescription')
                     ->with($this->identicalTo('stu'));
        $translation1->expects($this->once())
                     ->method('setId')
                     ->with($this->identicalTo($translationId1));

        $translation2 = $this->createMock(Translation::class);
        $translation2->expects($this->once())
                     ->method('setValue')
                     ->with($this->identicalTo('pqr'));
        $translation2->expects($this->once())
                     ->method('setId')
                     ->with($this->identicalTo($translationId2));

        $translation3 = $this->createMock(Translation::class);
        $translation3->expects($this->once())
                     ->method('setDescription')
                     ->with($this->identicalTo('yza'));
        $translation3->expects($this->once())
                     ->method('setId')
                     ->with($this->identicalTo($translationId3));

        $expectedResult = [$translation1, $translation2, $translation3];

        $this->validator->expects($this->exactly(3))
                        ->method('validateTranslation')
                        ->withConsecutive(
                            [$this->identicalTo($translation1)],
                            [$this->identicalTo($translation2)],
                            [$this->identicalTo($translation3)],
                        );

        $this->idCalculator->expects($this->exactly(3))
                           ->method('calculateIdOfTranslation')
                           ->withConsecutive(
                               [$this->identicalTo($translation1)],
                               [$this->identicalTo($translation2)],
                               [$this->identicalTo($translation3)],
                           )
                           ->willReturnOnConsecutiveCalls(
                               $translationId1,
                               $translationId2,
                               $translationId3,
                           );

        $instance = $this->createInstance(['createTranslationEntity']);
        $instance->expects($this->exactly(3))
                 ->method('createTranslationEntity')
                 ->withConsecutive(
                     [$this->identicalTo('ghi'), $this->identicalTo($type), $this->identicalTo($name)],
                     [$this->identicalTo('mno'), $this->identicalTo($type), $this->identicalTo($name)],
                     [$this->identicalTo('vwx'), $this->identicalTo($type), $this->identicalTo($name)],
                 )
                 ->willReturnOnConsecutiveCalls(
                     $translation1,
                     $translation2,
                     $translation3,
                 );

        $result = $this->invokeMethod(
            $instance,
            'createTranslationsFromLocalisedStrings',
            $type,
            $name,
            $values,
            $description,
        );

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateTranslationEntity(): void
    {
        $locale = 'abc';
        $type = 'def';
        $name = 'ghi';

        $expectedResult = new Translation();
        $expectedResult->setLocale($locale)
                       ->setType($type)
                       ->setName($name);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'createTranslationEntity', $locale, $type, $name);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFindItem(): void
    {
        $type = 'abc';
        $name = 'def';

        $item1 = new Item();
        $item1->type = 'foo';
        $item1->name = 'def';
        $item2 = new Item();
        $item2->type = 'abc';
        $item2->name = 'bar';
        $item3 = new Item();
        $item3->type = 'abc';
        $item3->name = 'def';

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getItems()->add($item1)
                               ->add($item2)
                               ->add($item3);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'findItem', $exportData, $type, $name);

        $this->assertSame($item3, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFindItemWithoutMatch(): void
    {
        $type = 'foo';
        $name = 'bar';

        $item1 = new Item();
        $item1->type = 'foo';
        $item1->name = 'def';
        $item2 = new Item();
        $item2->type = 'abc';
        $item2->name = 'bar';
        $item3 = new Item();
        $item3->type = 'abc';
        $item3->name = 'def';

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getItems()->add($item1)
                               ->add($item2)
                               ->add($item3);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'findItem', $exportData, $type, $name);

        $this->assertNull($result);
    }

    /**
     * @throws ReflectionException
     */
    public function testFilterDuplicatesToItems(): void
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
                     ->setValue('vwx');

        $translation4 = new Translation();
        $translation4->setLocale('bcd')
                     ->setValue('efg')
                     ->setDescription('hij');

        $translations = [$translation1, $translation2, $translation3, $translation4];
        $expectedResult = [$translation1, $translation4];

        $item1 = new Item();
        $item1->labels->set('abc', 'def');
        $item1->labels->set('jkl', 'mno');
        $item1->descriptions->set('abc', 'foo');
        $item1->descriptions->set('jkl', 'pqr');

        $item2 = new Item();
        $item2->labels->set('abc', 'def');
        $item2->labels->set('stu', 'vwx');
        $item2->descriptions->set('abc', 'bar');
        $item2->descriptions->set('stu', 'baz');

        $items = [$item1, $item2];

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'filterDuplicatesToItems', $translations, $items);

        $this->assertSame($expectedResult, $result);
    }

    public function testCleanup(): void
    {
        $instance = $this->createInstance();
        $instance->cleanup();

        $this->addToAssertionCount(1);
    }
}
