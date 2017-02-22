<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Tests\DependencyInjection\Extension;

use CoreSphere\ConsoleBundle\Contract\Executer\CommandExecuterInterface;
use CoreSphere\ConsoleBundle\DependencyInjection\Extension\CoreSphereConsoleExtension;
use CoreSphere\ConsoleBundle\Tests\Executer\CommandExecutorSource\SomeKernel;
use PHPUnit_Framework_TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class CoreSphereConsoleExtensionTest extends PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $containerBuilder = $this->createAndPrepareContainerBuilder();

        $extension = new CoreSphereConsoleExtension();
        $extension->load([], $containerBuilder);

        $commandExecuter = $containerBuilder->get('coresphere_console.executer');
        $this->assertInstanceOf(CommandExecuterInterface::class, $commandExecuter);
    }

    public function testPrepend()
    {
        $containerBuilder = new ContainerBuilder();

        $extension = new CoreSphereConsoleExtension();
        $extension->prepend($containerBuilder);

        $extensionConfig = $containerBuilder->getExtensionConfig($extension->getAlias());
        $this->assertSame([
            'resource' => '.',
            'type' => 'extra',
        ], $extensionConfig[0]);
    }

    /**
     * @return ContainerBuilder
     */
    private function createAndPrepareContainerBuilder()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setDefinition('kernel', new Definition(SomeKernel::class))
            ->setArguments(['prod', true]);

        return $containerBuilder;
    }
}
