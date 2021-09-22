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
use PHPUnit\Framework\TestCase;

class CommandExecuterTest extends TestCase
{
    public function testExecute()
    {
        $executer = $this->createExecuterWithKernel('prod');
        $result = $executer->execute('list');

        $this->assertSame('list', $result['input']);
        $this->assertContains('Lists commands', $result['output']);
        #$this->assertStringContainsString('Lists commands', $result['output']);
        $this->assertSame('prod', $result['environment']);
        $this->assertSame(0, $result['error_code']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testExecuteWithExplicitEnvironment()
    {
        $executer = $this->createExecuterWithKernel('prod');
        $result = $executer->execute('list --env=dev');

        $this->assertSame('list --env=dev', $result['input']);
        $this->assertContains('Lists commands', $result['output']);
        $this->assertSame('dev', $result['environment']);
        $this->assertSame(0, $result['error_code']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testExecuteNonExistingCommand()
    {
        $executer = $this->createExecuterWithKernel('dev');
        $result = $executer->execute('someNonExistingCommand');

        $this->assertSame('someNonExistingCommand', $result['input']);
        $this->assertContains('Command &quot;someNonExistingCommand&quot; is not defined.', $result['output']);
        $this->assertSame('dev', $result['environment']);
        $this->assertSame(1, $result['error_code']);
    }

    private function createExecuterWithKernel(string $env): CommandExecuter
    {
        $kernel = new SomeKernel($env, true);

        return new CommandExecuter($kernel);
    }
}
