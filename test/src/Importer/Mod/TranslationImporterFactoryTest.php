<?php

namespace FactorioItemBrowserTest\Api\Import\Importer\Mod;

use Doctrine\ORM\EntityManager;
use FactorioItemBrowser\Api\Import\Importer\Mod\TranslationImporter;
use FactorioItemBrowser\Api\Import\Importer\Mod\TranslationImporterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the TranslationImporterFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Importer\Mod\TranslationImporterFactory
 */
class TranslationImporterFactoryTest extends TestCase
{
    /**
     * Tests the invoking.
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        /* @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)
                          ->setMethods(['get'])
                          ->getMockForAbstractClass();
        $container->expects($this->once())
                  ->method('get')
                  ->with(EntityManager::class)
                  ->willReturn($this->createMock(EntityManager::class));

        $factory = new TranslationImporterFactory();
        $factory($container, TranslationImporter::class);
    }
}
