<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Controller;

use CoreSphere\ConsoleBundle\Command\ConsoleExecuteCommand;
use CoreSphere\ConsoleBundle\Contract\Executer\CommandExecuterInterface;
use CoreSphere\ConsoleBundle\Executer\QueueCommandExecuter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Templating\EngineInterface;

class ConsoleController
{
    /**
     * @var string
     */
    private $queueDir;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var CommandExecuterInterface
     */
    private $commandExecuter;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(
        EngineInterface $templating,
        CommandExecuterInterface $commandExecuter,
        Application $application,
        SessionInterface $session,
        string $environment,
        string $queueDir
    ) {
        $this->templating = $templating;
        $this->commandExecuter = $commandExecuter;
        $this->application = $application;
        $this->environment = $environment;
        $this->session = $session;
        $this->queueDir = $queueDir;
    }

    public function consoleAction(): Response
    {
        $this->ensureSessionStarted();

        return new Response(
            $this->templating->render(
                'CoreSphereConsoleBundle:Console:console.html.twig',
                [
                    'working_dir' => getcwd(),
                    'environment' => $this->environment,
                    'commands'    => $this->application->all(),
                ]
            )
        );
    }

    public function execAction(Request $request): Response
    {
        $this->ensureSessionStarted();
        $commands = $request->request->get('commands');
        $executedCommandsOutput = [];

        foreach ($commands as $command) {
            $result = $this->commandExecuter->execute($command, getcwd());
            $executedCommandsOutput[] = $result;

            if (0 !== $result['error_code']) {
                break;
            }
        }

        return new Response(
            $this->templating->render(
                'CoreSphereConsoleBundle:Console:result.json.twig',
                ['commands' => $executedCommandsOutput]
            )
        );
    }

    public function stateAction(): Response
    {
        $this->ensureSessionStarted();
        $sessionId = $this->session->getId();
        $commandDumpFile = QueueCommandExecuter::getCommandDumpFileName($this->queueDir, $sessionId);
        if (!file_exists($commandDumpFile)) {
            return new Response();
        }
        $content = file_get_contents($commandDumpFile);
        if ($this->commandCompeleted($content)) {
            unlink($commandDumpFile);
        }

        return new Response($content);
    }

    private function ensureSessionStarted(): void
    {
        if ($this->session->isStarted()) {
            $this->session->start();
        }
    }

    protected function commandCompeleted(string $content): bool
    {
        return substr($content, -strlen(ConsoleExecuteCommand::OUTPUT_END)) === ConsoleExecuteCommand::OUTPUT_END;
    }
}
