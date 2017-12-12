<?php declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Tests\Controller;

use CoreSphere\ConsoleBundle\Controller\ConsoleController;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\EngineInterface;

final class ConsoleControllerTest extends TestCase
{
    /**
     * @var array
     */
    private $renderArguments = [];

    public function testConsoleActionWorkingDir()
    {
        $controller = $this->createControllerWithEnvironment('prod');
        $controller->console();
        $this->assertSame(getcwd(), $this->renderArguments[1]['working_dir']);
    }

    public function testConsoleActionEnvironment()
    {
        $controller = $this->createControllerWithEnvironment('prod');
        $controller->console();
        $this->assertSame('prod', $this->renderArguments[1]['environment']);

        $controller = $this->createControllerWithEnvironment('dev');
        $controller->console();
        $this->assertSame('dev', $this->renderArguments[1]['environment']);
    }

    public function testConsoleActionCommands()
    {
        $controller = $this->createControllerWithEnvironment('prod');
        $controller->console();

        $this->assertCount(9, $this->renderArguments[1]['commands']);
    }

    public function testExecAction()
    {
        $controller = $this->createControllerWithEnvironment('prod');
        $request = new Request([], ['commands' => ['help', 'list']]);
        $controller->exec($request);

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
        $controller->exec($request);

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
}
