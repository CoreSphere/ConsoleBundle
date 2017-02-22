<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Tests\Controller;

use CoreSphere\ConsoleBundle\Console\ApplicationFactory;
use CoreSphere\ConsoleBundle\Contract\Executer\CommandExecuterInterface;
use CoreSphere\ConsoleBundle\Controller\ConsoleController;
use CoreSphere\ConsoleBundle\Tests\Source\KernelWithBundlesWithCommands;
use PHPUnit_Framework_TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\EngineInterface;

final class ConsoleControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $renderArguments = [];

    public function testConsoleActionWorkingDir()
    {
        $controller = $this->createControllerWithEnvironment('prod');
        $controller->consoleAction();
        $this->assertSame(getcwd(), $this->renderArguments[1]['working_dir']);
    }

    public function testConsoleActionEnvironment()
    {
        $controller = $this->createControllerWithEnvironment('prod');
        $controller->consoleAction();
        $this->assertSame('prod', $this->renderArguments[1]['environment']);

        $controller = $this->createControllerWithEnvironment('dev');
        $controller->consoleAction();
        $this->assertSame('dev', $this->renderArguments[1]['environment']);
    }

    public function testConsoleActionCommands()
    {
        $controller = $this->createControllerWithEnvironment('prod');
        $controller->consoleAction();

        $this->assertCount(9, $this->renderArguments[1]['commands']);
    }

    public function testExecAction()
    {
        $controller = $this->createControllerWithEnvironment('prod');
        $request = new Request([], ['commands' => ['help', 'list']]);
        $controller->execAction($request);

        $commandsOutput = $this->renderArguments[1]['commands'];
        $this->assertSame([
            [
                'output' => 'help-output',
                'error_code' => 0,
            ],
            [
                'output' => 'list-output',
                'error_code' => 0,
            ],
        ], $commandsOutput);
    }

    public function testExecActionWithError()
    {
        $controller = $this->createControllerWithEnvironment('prod');
        $request = new Request([], ['commands' => ['error-command', 'help']]);
        $controller->execAction($request);

        $this->assertSame([[
            'error_code' => 1,
        ]], $this->renderArguments[1]['commands']);
    }

    /**
     * @param string $environment
     *
     * @return ConsoleController
     */
    private function createControllerWithEnvironment($environment)
    {
        $templatingMock = $this->createTemplatingMock();
        $commandExecuterMock = $this->createCommandExecuterMock();
        $application = $this->createApplicationWithEnvironment($environment);

        return new ConsoleController(
            $templatingMock->reveal(),
            $commandExecuterMock->reveal(),
            $application,
            $environment
        );
    }

    /**
     * @return ObjectProphecy
     */
    private function createTemplatingMock()
    {
        $templatingMock = $this->prophesize(EngineInterface::class);
        $that = $this;
        $templatingMock->render(Argument::type('string'), Argument::type('array'))->will(
            function ($args) use ($that) {
                $that->renderArguments = $args;
            }
        );

        return $templatingMock;
    }

    /**
     * @return ObjectProphecy
     */
    private function createCommandExecuterMock()
    {
        $commandExecuterMock = $this->prophesize(CommandExecuterInterface::class);
        $commandExecuterMock->execute(Argument::exact('error-command'))
            ->willReturn([
                'error_code' => 1,
            ]);
        $commandExecuterMock->execute(Argument::exact('help'))
            ->willReturn([
                'output' => 'help-output',
                'error_code' => 0,
            ]);
        $commandExecuterMock->execute(Argument::exact('list'))
            ->willReturn([
                'output' => 'list-output',
                'error_code' => 0,
            ]);

        return $commandExecuterMock;
    }

    /**
     * @param string $environment
     *
     * @return Application
     */
    private function createApplicationWithEnvironment($environment)
    {
        $kernel = new KernelWithBundlesWithCommands($environment, true);
        $application = (new ApplicationFactory())->create($kernel);

        return $application;
    }
}
