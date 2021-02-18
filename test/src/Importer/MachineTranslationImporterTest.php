<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Translation;
use FactorioItemBrowser\Api\Database\Repository\TranslationRepository;
use FactorioItemBrowser\Api\Import\Helper\IdCalculator;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\MachineTranslationImporter;
use FactorioItemBrowser\Common\Constant\EntityType;
use FactorioItemBrowser\ExportData\Entity\Item;
use FactorioItemBrowser\ExportData\Entity\Machine as ExportMachine;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\Storage\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the MachineTranslationImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @covers \FactorioItemBrowser\Api\Import\Importer\MachineTranslationImporter
 */
class MachineTranslationImporterTest extends TestCase
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
     * @return MachineTranslationImporter&MockObject
     */
    private function createInstance(array $mockedMethods = []): MachineTranslationImporter
    {
        return $this->getMockBuilder(MachineTranslationImporter::class)
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
        $machine1 = $this->createMock(ExportMachine::class);
        $machine2 = $this->createMock(ExportMachine::class);
        $machine3 = $this->createMock(ExportMachine::class);

        $exportData = new ExportData($this->createMock(Storage::class), 'foo');
        $exportData->getMachines()->add($machine1)
                                  ->add($machine2)
                                  ->add($machine3);

        $instance = $this->createInstance();
        $result = $this->invokeMethod($instance, 'getExportEntities', $exportData);

        $this->assertEquals([$machine1, $machine2, $machine3], iterator_to_array($result));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateTranslationsForEntity(): void
    {
        $name = 'abc';
        $exportData = $this->createMock(ExportData::class);

        $machine = new ExportMachine();
        $machine->name = $name;

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

        $instance = $this->createInstance([
                             'createTranslationsFromLocalisedStrings',
                             'findItem',
                             'filterDuplicatesToItems',
                         ]);
        $instance->expects($this->once())
                 ->method('createTranslationsFromLocalisedStrings')
                 ->with(
                     $this->identicalTo(EntityType::MACHINE),
                     $this->identicalTo($name),
                     $this->identicalTo($machine->labels),
                     $this->identicalTo($machine->descriptions),
                 )
                 ->willReturn($translations);
        $instance->expects($this->once())
                 ->method('findItem')
                 ->with(
                     $this->identicalTo($exportData),
                     $this->identicalTo(EntityType::ITEM),
                     $this->identicalTo($name),
                 )
                 ->willReturn($item);
        $instance->expects($this->once())
                 ->method('filterDuplicatesToItems')
                 ->with($this->identicalTo($translations), $this->identicalTo($expectedItems))
                 ->willReturn($filteredTranslations);

        $result = $this->invokeMethod($instance, 'createTranslationsForEntity', $exportData, $machine);

        $this->assertSame($filteredTranslations, $result);
    }
}
