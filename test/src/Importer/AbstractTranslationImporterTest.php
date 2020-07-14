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
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\LocalisedString;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
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
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();

        $this->assertSame($this->entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($this->idCalculator, $this->extractProperty($importer, 'idCalculator'));
        $this->assertSame($this->repository, $this->extractProperty($importer, 'repository'));
        $this->assertSame($this->validator, $this->extractProperty($importer, 'validator'));
    }

    /**
     * Tests the prepare method.
     * @covers ::prepare
     */
    public function testPrepare(): void
    {
        $combination = $this->createMock(DatabaseCombination::class);

        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();

        $importer->prepare($combination);

        $this->addToAssertionCount(1);
    }

    /**
     * Tests the import method.
     * @throws DBALException
     * @covers ::import
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

        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->onlyMethods(['createTranslations'])
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('createTranslations')
                 ->with($this->identicalTo($exportData), $this->identicalTo($offset), $this->identicalTo($limit))
                 ->willReturn($entities);

        $importer->import($combination, $exportData, $offset, $limit);
    }

    /**
     * Tests the createTranslations method.
     * @throws ReflectionException
     * @covers ::createTranslations
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

        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->onlyMethods([
                             'getChunkedExportEntities',
                             'createTranslationsForEntity',
                         ])
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('getChunkedExportEntities')
                 ->with($this->identicalTo($exportData), $this->identicalTo($offset), $this->identicalTo($limit))
                 ->willReturn($entities);
        $importer->expects($this->exactly(2))
                 ->method('createTranslationsForEntity')
                 ->withConsecutive(
                     [$this->identicalTo($exportData), $this->identicalTo($entity1)],
                     [$this->identicalTo($exportData), $this->identicalTo($entity2)],
                 )
                 ->willReturnOnConsecutiveCalls(
                     [$translation1, $translation2],
                     [$translation3, $translation4],
                 );

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

        $result = $this->invokeMethod($importer, 'createTranslations', $exportData, $offset, $limit);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the createTranslationsFromLocalisedStrings method.
     * @throws ReflectionException
     * @covers ::createTranslationsFromLocalisedStrings
     */
    public function testCreateTranslationsFromLocalisedStrings(): void
    {
        $type = 'abc';
        $name = 'def';

        $values = new LocalisedString();
        $values->addTranslation('ghi', 'jkl')
               ->addTranslation('mno', 'pqr');

        $description = new LocalisedString();
        $description->addTranslation('ghi', 'stu')
                    ->addTranslation('vwx', 'yza');

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

        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->onlyMethods([
                             'createTranslationEntity',
                         ])
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();
        $importer->expects($this->exactly(3))
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
            $importer,
            'createTranslationsFromLocalisedStrings',
            $type,
            $name,
            $values,
            $description,
        );

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the createTranslationEntity method.
     * @throws ReflectionException
     * @covers ::createTranslationEntity
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

        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();
        $result = $this->invokeMethod($importer, 'createTranslationEntity', $locale, $type, $name);

        $this->assertEquals($expectedResult, $result);
    }
    
    /**
     * Tests the findItem method.
     * @throws ReflectionException
     * @covers ::findItem
     */
    public function testFindItem(): void
    {
        $type = 'abc';
        $name = 'def';

        $item1 = new Item();
        $item1->setType('foo')
              ->setName('def');

        $item2 = new Item();
        $item2->setType('abc')
              ->setName('bar');
        
        $item3 = new Item();
        $item3->setType('abc')
              ->setName('def');

        $combination = new ExportCombination();
        $combination->setItems([$item1, $item2, $item3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();
        $result = $this->invokeMethod($importer, 'findItem', $exportData, $type, $name);
        
        $this->assertSame($item3, $result);
    }

    /**
     * Tests the findItem method.
     * @throws ReflectionException
     * @covers ::findItem
     */
    public function testFindItemWithoutMatch(): void
    {
        $type = 'foo';
        $name = 'bar';

        $item1 = new Item();
        $item1->setType('foo')
              ->setName('def');

        $item2 = new Item();
        $item2->setType('abc')
              ->setName('bar');

        $item3 = new Item();
        $item3->setType('abc')
              ->setName('def');

        $combination = new ExportCombination();
        $combination->setItems([$item1, $item2, $item3]);

        $exportData = new ExportData($combination, $this->createMock(StorageInterface::class));

        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();
        $result = $this->invokeMethod($importer, 'findItem', $exportData, $type, $name);

        $this->assertNull($result);
    }

    /**
     * Tests the filterDuplicatesToItems method.
     * @throws ReflectionException
     * @covers ::filterDuplicatesToItems
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
        $item1->getLabels()->addTranslation('abc', 'def')
                           ->addTranslation('jkl', 'mno');
        $item1->getDescriptions()->addTranslation('abc', 'foo')
                                 ->addTranslation('jkl', 'pqr');

        $item2 = new Item();
        $item2->getLabels()->addTranslation('abc', 'def')
                           ->addTranslation('stu', 'vwx');
        $item2->getDescriptions()->addTranslation('abc', 'bar')
                                 ->addTranslation('stu', 'baz');

        $items = [$item1, $item2];

        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();
        $result = $this->invokeMethod($importer, 'filterDuplicatesToItems', $translations, $items);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        /* @var AbstractTranslationImporter&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractTranslationImporter::class)
                         ->setConstructorArgs([
                             $this->entityManager,
                             $this->idCalculator,
                             $this->repository,
                             $this->validator,
                         ])
                         ->getMockForAbstractClass();

        $importer->cleanup();

        $this->addToAssertionCount(1);
    }
}
