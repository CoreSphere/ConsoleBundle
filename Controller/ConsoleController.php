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
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Twig\Environment;

class ConsoleController
{
    private Environment $templating;
    private CommandExecuterInterface $commandExecuter;
    private Application $application;
    private string $environment;

    public function __construct(
        Environment $templating,
        CommandExecuterInterface $commandExecuter,
        Application $application,
        $environment
    ) {
        $this->templating = $templating;
        $this->commandExecuter = $commandExecuter;
        $this->application = $application;
        $this->environment = $environment;
    }

    /**
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\LoaderError
     */
    public function consoleAction(): Response
    {
        return new Response(

            $this->templating->render('@CoreSphereConsole/Console/console.html.twig', [
                'working_dir' => getcwd(),
                'environment' => $this->environment,
                'commands' => $this->application->all(),
            ])
        );
    }

    /**
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\LoaderError
     */
    public function execAction(Request $request): Response
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
                '@CoreSphereConsole/Console/result.json.twig',
                ['commands' => $executedCommandsOutput]
            )
        );
    }
}
