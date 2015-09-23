<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Tests\Executer;

use CoreSphere\ConsoleBundle\Executer\CommandExecuter;
use CoreSphere\ConsoleBundle\Tests\Executer\CommandExecutorSource\SomeKernel;
use PHPUnit_Framework_TestCase;

class CommandExecuterTest extends PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $executer = $this->createExecuterWithKernel('prod', true);
        $result = $executer->execute('list');

        $this->assertSame('list', $result['input']);
        $this->assertContains('Lists commands', $result['output']);
        $this->assertSame('prod', $result['environment']);
        $this->assertSame(0, $result['error_code']);
    }
    
    public function testExecuteWithExplicitEnvironment()
    {
        $executer = $this->createExecuterWithKernel('prod', true);
        $result = $executer->execute('list --env=dev');

        $this->assertSame('list --env=dev', $result['input']);
        $this->assertContains('Lists commands', $result['output']);
        $this->assertSame('dev', $result['environment']);
        $this->assertSame(0, $result['error_code']);
    }

    public function testExecuteNonExistingCommand()
    {
        $executer = $this->createExecuterWithKernel('dev', true);
        $result = $executer->execute('someNonExistingCommand');

        $this->assertSame('someNonExistingCommand', $result['input']);
        $this->assertContains('Command &quot;someNonExistingCommand&quot; is not defined.', $result['output']);
        $this->assertSame('dev', $result['environment']);
        $this->assertSame(1, $result['error_code']);
    }

    /**
     * @param string $env
     * @param bool $debug
     * @return CommandExecuter
     */
    private function createExecuterWithKernel($env, $debug)
    {
        $kernel = new SomeKernel($env, $debug);
        return new CommandExecuter($kernel);
    }
}
