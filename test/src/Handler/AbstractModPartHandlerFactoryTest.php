<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Handler;

use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\Constant\ServiceName;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Handler\AbstractModPartHandlerFactory;
use FactorioItemBrowser\Api\Import\Importer\Mod\CombinationImporter;
use FactorioItemBrowser\Api\Import\Importer\Mod\DependencyImporter;
use FactorioItemBrowser\Api\Import\Importer\Mod\ModImporterInterface;
use FactorioItemBrowser\Api\Import\Importer\Mod\ThumbnailImporter;
use FactorioItemBrowser\Api\Import\Importer\Mod\TranslationImporter;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the AbstractModPartHandlerFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Handler\AbstractModPartHandlerFactory
 */
class AbstractModPartHandlerFactoryTest extends TestCase
{
    /**
     * Provides the data for the canCreate test.
     * @return array
     */
    public function provideCanCreate(): array
    {
        return [
            [ServiceName::MOD_COMBINATIONS_HANDLER, true],
            [ServiceName::MOD_DEPENDENCIES_HANDLER, true],
            [ServiceName::MOD_THUMBNAIL_HANDLER, true],
            [ServiceName::MOD_TRANSLATIONS_HANDLER, true],
            ['foo', false],
        ];
    }

    /**
     * Tests the canCreate method.
     * @param string $requestedName
     * @param bool $expectedResult
     * @throws ReflectionException
     * @covers ::canCreate
     * @dataProvider provideCanCreate
     */
    public function testCanCreate(string $requestedName, bool $expectedResult): void
    {
        /* @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);

        $factory = new AbstractModPartHandlerFactory();
        $result = $factory->canCreate($container, $requestedName);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Provides the data for the invoke test.
     * @return array
     */
    public function provideInvoke(): array
    {
        return [
            [ServiceName::MOD_COMBINATIONS_HANDLER, CombinationImporter::class],
            [ServiceName::MOD_DEPENDENCIES_HANDLER, DependencyImporter::class],
            [ServiceName::MOD_THUMBNAIL_HANDLER, ThumbnailImporter::class],
            [ServiceName::MOD_TRANSLATIONS_HANDLER, TranslationImporter::class],
        ];
    }

    /**
     * Tests the invoking.
     * @param string $requestedName
     * @param string $expectedImporterClass
     * @throws ReflectionException
     * @covers ::__invoke
     * @dataProvider provideInvoke
     */
    public function testInvoke(string $requestedName, string $expectedImporterClass): void
    {
        /* @var ContainerInterface&MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(3))
                  ->method('get')
                  ->withConsecutive(
                      [$this->identicalTo(ModRepository::class)],
                      [$this->identicalTo(RegistryService::class)],
                      [$this->identicalTo($expectedImporterClass)]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $this->createMock(ModRepository::class),
                      $this->createMock(RegistryService::class),
                      $this->createMock(ModImporterInterface::class)
                  );

        $factory = new AbstractModPartHandlerFactory();
        $factory($container, $requestedName);
    }
}
