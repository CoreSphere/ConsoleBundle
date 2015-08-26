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

use CoreSphere\ConsoleBundle\Contract\Executer\CommandExecuterInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

class ConsoleController
{
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

    public function __construct(
        EngineInterface $templating,
        CommandExecuterInterface $commandExecuter,
        Application $application,
        $environment
    ) {
        $this->templating = $templating;
        $this->commandExecuter = $commandExecuter;
        $this->application = $application;
        $this->environment = $environment;
    }

    public function consoleAction()
    {
        return new Response(
            $this->templating->render('CoreSphereConsoleBundle:Console:console.html.twig', [
                'working_dir' => getcwd(),
                'environment' => $this->environment,
                'commands' => $this->application->all(),
            ])
        );
    }

    public function execAction(Request $request)
    {
        $commands = $request->request->get('commands');
        $executedCommandsOutput = [];

        foreach ($commands as $command) {
            $result = $this->commandExecuter->execute($command);
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
}
