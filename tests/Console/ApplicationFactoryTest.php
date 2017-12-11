<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Tests\Console;

use CoreSphere\ConsoleBundle\Console\ApplicationFactory;
use CoreSphere\ConsoleBundle\Tests\Source\KernelWithBundlesWithCommands;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

final class ApplicationFactoryTest extends TestCase
{
    public function testCreate()
    {
        $kernel = new KernelWithBundlesWithCommands('prod', true);
        $kernel->boot();

        $this->assertInstanceOf(
            Application::class,
            (new ApplicationFactory())->create($kernel)
        );
    }

    /**
     * @dataProvider provideTestCommandRegistration()
     */
    public function testCommandsRegistration(string $environment, int $commandCount)
    {
        $kernel = new KernelWithBundlesWithCommands($environment, false);
        $kernel->boot();

        $application = (new ApplicationFactory())->create($kernel);

        $commands = $application->all();
        $this->assertCount($commandCount, $commands);
    }

    /**
     * @return string[]
     */
    public function provideTestCommandRegistration()
    {
        return [
            ['prod', 9],
            ['dev', 3],
            ['test', 2],
        ];
    }
}
