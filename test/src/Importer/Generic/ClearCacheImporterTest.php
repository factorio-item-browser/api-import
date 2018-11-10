<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer\Generic;

use BluePsyduck\Common\Test\ReflectionTrait;
use FactorioItemBrowser\Api\Database\Repository\CachedSearchResultRepository;
use FactorioItemBrowser\Api\Import\Importer\Generic\ClearCacheImporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the ClearCacheImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Generic\ClearCacheImporter
 */
class ClearCacheImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the constructing.
     * @throws ReflectionException
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        /* @var CachedSearchResultRepository $cachedSearchResultRepository */
        $cachedSearchResultRepository = $this->createMock(CachedSearchResultRepository::class);

        $importer = new ClearCacheImporter($cachedSearchResultRepository);

        $this->assertSame(
            $cachedSearchResultRepository,
            $this->extractProperty($importer, 'cachedSearchResultRepository')
        );
    }

    /**
     * Tests the import method.
     * @covers ::import
     */
    public function testImport(): void
    {
        /* @var CachedSearchResultRepository|MockObject $cachedSearchResultRepository */
        $cachedSearchResultRepository = $this->getMockBuilder(CachedSearchResultRepository::class)
                                             ->setMethods(['clear'])
                                             ->disableOriginalConstructor()
                                             ->getMock();
        $cachedSearchResultRepository->expects($this->once())
                                     ->method('clear');

        $importer = new ClearCacheImporter($cachedSearchResultRepository);
        $importer->import();
    }
}
