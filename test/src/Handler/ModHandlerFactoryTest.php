<?php

declare(strict_types=1);

namespace FactorioItemBrowserTest\Api\Import\Handler;

use BluePsyduck\Common\Test\ReflectionTrait;
use Doctrine\ORM\EntityManagerInterface;
use FactorioItemBrowser\Api\Database\Repository\ModRepository;
use FactorioItemBrowser\Api\Import\ExportData\RegistryService;
use FactorioItemBrowser\Api\Import\Handler\ModHandler;
use FactorioItemBrowser\Api\Import\Handler\ModHandlerFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test of the ModHandlerFactory class.
 *
 * @author BluePsyduck <bluepsyduck@gmx.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3
 * @coversDefaultClass \FactorioItemBrowser\Api\Import\Handler\ModHandlerFactory
 */
class ModHandlerFactoryTest extends TestCase
{
    use ReflectionTrait;

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
        $container->expects($this->exactly(3))
                  ->method('get')
                  ->withConsecutive(
                      [EntityManagerInterface::class],
                      [ModRepository::class],
                      [RegistryService::class]
                  )
                  ->willReturnOnConsecutiveCalls(
                      $this->createMock(EntityManagerInterface::class),
                      $this->createMock(ModRepository::class),
                      $this->createMock(RegistryService::class)
                  );

        $factory = new ModHandlerFactory();
        $factory($container, ModHandler::class);
    }
}
