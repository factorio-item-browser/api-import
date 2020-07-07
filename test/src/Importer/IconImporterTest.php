<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Entity\Combination as DatabaseCombination;
use FactorioItemBrowser\Api\Database\Entity\Icon as DatabaseIcon;
use FactorioItemBrowser\Api\Database\Entity\IconImage;
use FactorioItemBrowser\Api\Database\Repository\IconRepository;
use FactorioItemBrowser\Api\Import\Helper\DataCollector;
use FactorioItemBrowser\Api\Import\Helper\Validator;
use FactorioItemBrowser\Api\Import\Importer\IconImporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;

/**
 * The PHPUnit test of the IconImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\IconImporter
 */
class IconImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * The mocked data collector.
     * @var DataCollector&MockObject
     */
    protected $dataCollector;

    /**
     * The mocked entity manager.
     * @var EntityManagerInterface&MockObject
     */
    protected $entityManager;

    /**
     * The mocked repository.
     * @var IconRepository&MockObject
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

        $this->dataCollector = $this->createMock(DataCollector::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(IconRepository::class);
        $this->validator = $this->createMock(Validator::class);
    }

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $importer = new IconImporter($this->dataCollector, $this->entityManager, $this->repository, $this->validator);

        $this->assertSame($this->dataCollector, $this->extractProperty($importer, 'dataCollector'));
        $this->assertSame($this->entityManager, $this->extractProperty($importer, 'entityManager'));
        $this->assertSame($this->repository, $this->extractProperty($importer, 'repository'));
        $this->assertSame($this->validator, $this->extractProperty($importer, 'validator'));
    }

    /**
     * Tests the prepare method.
     * @covers ::prepare
     */
    public function testPrepare(): void
    {
        $combinationId = $this->createMock(UuidInterface::class);

        $combination = new DatabaseCombination();
        $combination->setId($combinationId);

        $this->repository->expects($this->once())
                         ->method('clearCombination')
                         ->with($this->identicalTo($combinationId));

        $importer = new IconImporter($this->dataCollector, $this->entityManager, $this->repository, $this->validator);
        $importer->prepare($combination);
    }

    /**
     * Tests the createIcon method.
     * @throws ReflectionException
     * @covers ::createIcon
     */
    public function testCreateIcon(): void
    {
        $type = 'abc';
        $name = 'def';
        $imageId = 'ghi';
        $data = [$type, $name, $imageId];

        $combination = $this->createMock(DatabaseCombination::class);
        $iconImage = $this->createMock(IconImage::class);

        $expectedResult = new DatabaseIcon();
        $expectedResult->setType('abc')
                       ->setName('def')
                       ->setImage($iconImage)
                       ->setCombination($combination);

        $this->dataCollector->expects($this->once())
                            ->method('getIconImage')
                            ->with($this->identicalTo($imageId))
                            ->willReturn($iconImage);

        $this->validator->expects($this->once())
                        ->method('validateIcon')
                        ->with($this->equalTo($expectedResult));

        $importer = new IconImporter($this->dataCollector, $this->entityManager, $this->repository, $this->validator);
        $result = $this->invokeMethod($importer, 'createIcon', $data, $combination);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the cleanup method.
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $importer = new IconImporter($this->dataCollector, $this->entityManager, $this->repository, $this->validator);
        $importer->cleanup();

        $this->addToAssertionCount(1);
    }
}
