<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Importer;

use BluePsyduck\TestHelper\ReflectionTrait;
use FactorioItemBrowser\Api\Import\Importer\AbstractImporter;
use FactorioItemBrowser\ExportData\ExportData;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * The PHPUnit test of the AbstractImporter class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\AbstractImporter
 */
class AbstractImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Tests the count method.
     * @covers ::count
     */
    public function testCount(): void
    {
        $entities = [
            'abc',
            'def',
            'ghi',
        ];
        $expectedResult = 3;

        $exportData = $this->createMock(ExportData::class);

        /* @var AbstractImporter<string>&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractImporter::class)
                         ->onlyMethods(['getExportEntities'])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('getExportEntities')
                 ->willReturnCallback(function() use ($entities): Generator {
                     yield from $entities;
                 });

        $result = $importer->count($exportData);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getChunkedExportEntities method.
     * @throws ReflectionException
     * @covers ::getChunkedExportEntities
     */
    public function testGetChunkedExportEntities(): void
    {
        $offset = 1;
        $limit = 2;
        $entities = [
            'abc',
            'def',
            'ghi',
            'jkl',
            'mno',
        ];
        $expectedResult = [
            'def',
            'ghi',
        ];

        $exportData = $this->createMock(ExportData::class);

        /* @var AbstractImporter<string>&MockObject $importer */
        $importer = $this->getMockBuilder(AbstractImporter::class)
                         ->onlyMethods(['getExportEntities'])
                         ->getMockForAbstractClass();
        $importer->expects($this->once())
                 ->method('getExportEntities')
                 ->willReturnCallback(function() use ($entities): Generator {
                     yield from $entities;
                 });

        $result = $this->invokeMethod($importer, 'getChunkedExportEntities', $exportData, $offset, $limit);

        $this->assertEquals($expectedResult, $result);
    }
}
