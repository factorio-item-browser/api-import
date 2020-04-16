<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Command;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\CombinationRepository;
use FactorioItemBrowser\Api\Database\Repository\IconImageRepository;
use FactorioItemBrowser\Api\Import\Command\ImportImagesCommand;
use FactorioItemBrowser\Api\Import\Console\Console;
use FactorioItemBrowser\Api\Import\Constant\CommandName;
use FactorioItemBrowser\ExportData\Entity\Combination as ExportCombination;
use FactorioItemBrowser\ExportData\Entity\Icon;
use FactorioItemBrowser\ExportData\ExportData;
use FactorioItemBrowser\ExportData\ExportDataService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;

/**
 * The PHPUnit test of the ImportImagesCommand class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Command\ImportImagesCommand
 */
class ImportImagesCommandTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked combination repository.
     * @var CombinationRepository&MockObject
     */
    protected $combinationRepository;

    /**
     * The mocked console.
     * @var Console&MockObject
     */
    protected $console;

    /**
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

    /**
     * The mocked export data service.
     * @var ExportDataService&MockObject
     */
    protected $exportDataService;

    /**
     * The mocked icon image repository.
     * @var IconImageRepository&MockObject
     */
    protected $iconImageRepository;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->combinationRepository = $this->createMock(CombinationRepository::class);
        $this->console = $this->createMock(Console::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->exportDataService = $this->createMock(ExportDataService::class);
        $this->iconImageRepository = $this->createMock(IconImageRepository::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $command = new ImportImagesCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportDataService,
            $this->iconImageRepository
        );

        $this->assertSame($this->combinationRepository, $this->extractProperty($command, 'combinationRepository'));
        $this->assertSame($this->console, $this->extractProperty($command, 'console'));
        $this->assertSame($this->entityManager, $this->extractProperty($command, 'entityManager'));
        $this->assertSame($this->exportDataService, $this->extractProperty($command, 'exportDataService'));
        $this->assertSame($this->iconImageRepository, $this->extractProperty($command, 'iconImageRepository'));
    }

    /**
     * Tests the configure method.
     * @throws ReflectionException
     * @covers ::configure
     */
    public function testConfigure(): void
    {
        /* @var ImportImagesCommand&MockObject $command */
        $command = $this->getMockBuilder(ImportImagesCommand::class)
                        ->onlyMethods(['setName', 'setDescription', 'addArgument'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportDataService,
                            $this->iconImageRepository
                        ])
                        ->getMock();
        $command->expects($this->once())
                ->method('setName')
                ->with($this->identicalTo(CommandName::IMPORT_IMAGES));
        $command->expects($this->once())
                ->method('setDescription')
                ->with($this->isType('string'));
        $command->expects($this->once())
                ->method('addArgument')
                ->with(
                    $this->identicalTo('combination'),
                    $this->identicalTo(InputArgument::REQUIRED),
                    $this->isType('string')
                );

        $this->invokeMethod($command, 'configure');
    }

    /**
     * Tests the getLabel method.
     * @throws ReflectionException
     * @covers ::getLabel
     */
    public function testGetLabel(): void
    {
        $command = new ImportImagesCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportDataService,
            $this->iconImageRepository
        );
        $result = $this->invokeMethod($command, 'getLabel');

        $this->assertSame('Processing the images of the icons', $result);
    }

    /**
     * Tests the import method.
     * @throws ReflectionException
     * @covers ::import
     */
    public function testImport(): void
    {
        $icon1 = new Icon();
        $icon1->setId('abc');
        $icon2 = new Icon();
        $icon2->setId('def');
        $icon3 = new Icon();
        $icon3->setId('ghi');

        /* @var IconImage&MockObject $image1 */
        $image1 = $this->createMock(IconImage::class);
        $image1->expects($this->once())
               ->method('setContents')
               ->with($this->identicalTo('jkl'));

        /* @var IconImage&MockObject $image2 */
        $image2 = $this->createMock(IconImage::class);
        $image2->expects($this->once())
               ->method('setContents')
               ->with($this->identicalTo('mno'));

        $exportCombination = new ExportCombination();
        $exportCombination->setIcons([$icon1, $icon2, $icon3]);

        /* @var ExportData&MockObject $exportData */
        $exportData = $this->createMock(ExportData::class);
        $exportData->expects($this->once())
                   ->method('getCombination')
                   ->willReturn($exportCombination);
        $exportData->expects($this->exactly(2))
                   ->method('getRenderedIcon')
                   ->withConsecutive(
                       [$this->identicalTo($icon1)],
                       [$this->identicalTo($icon3)]
                   )
                   ->willReturnOnConsecutiveCalls(
                       'jkl',
                       'mno'
                   );

        /* @var DatabaseCombination&MockObject $combination */
        $combination = $this->createMock(DatabaseCombination::class);

        $this->console->expects($this->exactly(3))
                      ->method('writeAction')
                      ->withConsecutive(
                          [$this->identicalTo('Importing image of icon abc')],
                          [$this->identicalTo('Importing image of icon def')],
                          [$this->identicalTo('Importing image of icon ghi')]
                      );

        /* @var ImportImagesCommand&MockObject $command */
        $command = $this->getMockBuilder(ImportImagesCommand::class)
                        ->onlyMethods(['getImage', 'persist'])
                        ->setConstructorArgs([
                            $this->combinationRepository,
                            $this->console,
                            $this->entityManager,
                            $this->exportDataService,
                            $this->iconImageRepository
                        ])
                        ->getMock();
        $command->expects($this->exactly(3))
                ->method('getImage')
                ->withConsecutive(
                    [$this->identicalTo($icon1)],
                    [$this->identicalTo($icon2)],
                    [$this->identicalTo($icon3)]
                )
                ->willReturnOnConsecutiveCalls(
                    $image1,
                    null,
                    $image2
                );
        $command->expects($this->exactly(2))
                ->method('persist')
                ->withConsecutive(
                    [$this->identicalTo($image1)],
                    [$this->identicalTo($image2)]
                );

        $this->invokeMethod($command, 'import', $exportData, $combination);
    }

    /**
     * Tests the getImage method.
     * @throws ReflectionException
     * @covers ::getImage
     */
    public function testGetImage(): void
    {
        $icon = new Icon();
        $icon->setId('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        $expectedIconId = Uuid::fromString('70acdb0f-36ca-4b30-9687-2baaade94cd3');

        /* @var IconImage&MockObject $image */
        $image = $this->createMock(IconImage::class);

        $this->iconImageRepository->expects($this->once())
                                  ->method('findByIds')
                                  ->with($this->equalTo([$expectedIconId]))
                                  ->willReturn([$image]);

        $command = new ImportImagesCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportDataService,
            $this->iconImageRepository
        );
        $result = $this->invokeMethod($command, 'getImage', $icon);

        $this->assertSame($image, $result);
    }

    /**
     * Tests the persist method.
     * @throws ReflectionException
     * @covers ::persist
     */
    public function testPersist(): void
    {
        /* @var IconImage&MockObject $image */
        $image = $this->createMock(IconImage::class);

        $this->entityManager->expects($this->once())
                            ->method('persist')
                            ->with($this->identicalTo($image));
        $this->entityManager->expects($this->once())
                            ->method('flush');
        $this->entityManager->expects($this->once())
                            ->method('detach')
                            ->with($this->identicalTo($image));

        $command = new ImportImagesCommand(
            $this->combinationRepository,
            $this->console,
            $this->entityManager,
            $this->exportDataService,
            $this->iconImageRepository
        );
        $this->invokeMethod($command, 'persist', $image);
    }
}
