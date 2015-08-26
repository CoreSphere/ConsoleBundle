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
use CoreSphere\ConsoleBundle\Tests\Console\ApplicationFactorySource\KernelWithBundleWithCommands;
use PHPUnit_Framework_TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

final class ApplicationFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $kernel = new KernelWithBundleWithCommands('prod', true);
        $application = (new ApplicationFactory())->create($kernel);

        $this->assertInstanceOf(Application::class, $application);
    }

    public function testCommandsRegistration()
    {
        $kernel = new KernelWithBundleWithCommands('prod', false);
        $application = (new ApplicationFactory())->create($kernel);

        $commands = $application->all();
        $this->assertCount(2, $commands);

        $kernel = new KernelWithBundleWithCommands('test', false);
        $application = (new ApplicationFactory())->create($kernel);

        $commands = $application->all();
        $this->assertCount(2, $commands);

        $kernel = new KernelWithBundleWithCommands('dev', false);
        $application = (new ApplicationFactory())->create($kernel);

        $commands = $application->all();
        $this->assertCount(2, $commands);
    }
}
