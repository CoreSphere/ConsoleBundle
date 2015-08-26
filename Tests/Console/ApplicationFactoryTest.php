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
use PHPUnit_Framework_TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

final class ApplicationFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $kernel = new KernelWithBundlesWithCommands('prod', true);

        $this->assertInstanceOf(
            Application::class,
            (new ApplicationFactory())->create($kernel)
        );
    }

    /**
     * @dataProvider provideTestCommandRegistration()
     * @param string $environment
     * @param int $commandCount
     */
    public function testCommandsRegistration($environment, $commandCount, $commandNames)
    {
        $kernel = new KernelWithBundlesWithCommands($environment, false);
        $application = (new ApplicationFactory())->create($kernel);

        $commands = $application->all();
        $this->assertCount($commandCount, $commands);
        $this->assertSame($commandNames, array_keys($commands));
    }

    /**
     * @return string[]
     */
    public function provideTestCommandRegistration()
    {
        return [
            ['prod', 9, [
                'help',
                'list',
                'doctrine:migrations:version',
                'doctrine:migrations:status',
                'doctrine:migrations:diff',
                'doctrine:migrations:execute',
                'doctrine:migrations:migrate',
                'doctrine:migrations:latest',
                'doctrine:migrations:generate'
            ]],
            ['dev', 3, ['help', 'list', 'doctrine:fixtures:load']],
            ['test', 2, ['help', 'list']],
        ];
    }

    public function testCommandsRegistrationWithAlreadyRegisteredCommands()
    {
        $kernel = new KernelWithBundlesWithCommands('prod', false);
        $kernel->boot();
        $application = (new ApplicationFactory())->create($kernel);

        $this->assertCount(9, $application->all());
    }
}
