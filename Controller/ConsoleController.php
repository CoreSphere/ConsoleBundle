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
use CoreSphere\ConsoleBundle\Executer\ProcessCommandExecuter;
use CoreSphere\ConsoleBundle\Executer\QueueCommandExecuter;
use CoreSphere\ConsoleBundle\Executer\SymfonyCommandExecuter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Templating\EngineInterface;

class ConsoleController
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var QueueCommandExecuter
     */
    private $asyncCommandExecuter;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $queueDir;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SymfonyCommandExecuter
     */
    private $syncCommandExecuter;

    /**
     * @var ProcessCommandExecuter
     */
    private $processCommandExecuter;

    /**
     * @var EngineInterface
     */
    private $templating;

    public function __construct(
        EngineInterface        $templating,
        QueueCommandExecuter   $asyncCommandExecuter,
        SymfonyCommandExecuter $symfonyCommandExecuter,
        ProcessCommandExecuter $processCommandExecuter,
        Application            $application,
        SessionInterface       $session,
        string                 $environment,
        string                 $queueDir
    ) {
        $this->templating = $templating;
        $this->asyncCommandExecuter = $asyncCommandExecuter;
        $this->syncCommandExecuter = $symfonyCommandExecuter;
        $this->application = $application;
        $this->environment = $environment;
        $this->session = $session;
        $this->queueDir = $queueDir;
        $this->processCommandExecuter = $processCommandExecuter;
    }

    public function consoleAction(Request $request): Response
    {
        $this->ensureSessionStarted();

        return new Response(
            $this->templating->render(
                'CoreSphereConsoleBundle:Console:console.html.twig',
                [
                    'working_dir' => getcwd(),
                    'environment' => $this->environment,
                    'commands'    => $this->application->all(),
                    'context'     => $request->get('context', 'async'),
                ]
            )
        );
    }

    private function ensureSessionStarted(): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
    }

    public function execAction(Request $request): Response
    {
        $this->ensureSessionStarted();
        $commands = $request->request->get('commands');
        $executedCommandsOutput = [];
        $context = $request->get('context', 'async');

        foreach ($commands as $command) {
            if ($context === 'process') {
                $result = $this->processCommandExecuter->execute($command, getcwd());
            } elseif ($context === 'sync') {
                $result = $this->syncCommandExecuter->execute($command, getcwd());
            } elseif ($context === 'stream') {
                $result = $this->asyncCommandExecuter->execute($command, getcwd());
            } else {
                $result = $this->asyncCommandExecuter->execute($command, getcwd(), false);
            }
            $executedCommandsOutput[] = $result;

            if (0 !== $result['error_code']) {
                break;
            }
        }

        return new JsonResponse(['results' => $executedCommandsOutput]);
    }

    public function stateAction(Request $request): Response
    {
        $this->ensureSessionStarted();
        $sessionId = $request->query->get('id', $this->session->getId());
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

    protected function commandCompeleted(string $content): bool
    {
        return substr($content, -strlen(ConsoleExecuteCommand::OUTPUT_END)) === ConsoleExecuteCommand::OUTPUT_END;
    }
}
