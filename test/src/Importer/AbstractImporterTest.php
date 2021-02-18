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
 * @covers \FactorioItemBrowser\Api\Import\Importer\AbstractImporter
 */
class AbstractImporterTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @param array<string> $mockedMethods
     * @return AbstractImporter<mixed>&MockObject
     */
    private function createInstance(array $mockedMethods = []): AbstractImporter
    {
        return $this->getMockBuilder(AbstractImporter::class)
                    ->disableProxyingToOriginalMethods()
                    ->onlyMethods($mockedMethods)
                    ->getMockForAbstractClass();
    }

    public function testCount(): void
    {
        $entities = [
            'abc',
            'def',
            'ghi',
        ];
        $expectedResult = 3;

        $exportData = $this->createMock(ExportData::class);

        $instance = $this->createInstance(['getExportEntities']);
        $instance->expects($this->once())
                 ->method('getExportEntities')
                 ->willReturnCallback(function () use ($entities): Generator {
                     yield from $entities;
                 });

        $result = $instance->count($exportData);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @throws ReflectionException
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

        $instance = $this->createInstance(['getExportEntities']);
        $instance->expects($this->once())
                 ->method('getExportEntities')
                 ->willReturnCallback(function () use ($entities): Generator {
                     yield from $entities;
                 });

        $result = $this->invokeMethod($instance, 'getChunkedExportEntities', $exportData, $offset, $limit);

        $this->assertEquals($expectedResult, $result);
    }
}
