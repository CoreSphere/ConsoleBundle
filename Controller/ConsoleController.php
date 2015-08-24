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

use CoreSphere\ConsoleBundle\Executer\CommandExecuter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class ConsoleController extends Controller
{
    public function consoleAction()
    {
        /** @var Kernel $kernel */
        $kernel = $this->get('kernel');
        $application = new Application($kernel);

        chdir($kernel->getRootDir().'/..');

        foreach ($kernel->getBundles() as $bundle) {
            /** @var Bundle $bundle */
            $bundle->registerCommands($application);
        }

        return $this->render('CoreSphereConsoleBundle:Console:console.html.twig', [
            'working_dir' => getcwd(),
            'environment' => $kernel->getEnvironment(),
            'commands' => $application->all(),
        ]);
    }

    public function execAction(Request $request)
    {
        $executer = new CommandExecuter($this->get('kernel'));
        $commands = $request->request->get('commands');
        $executedCommands = [];

        foreach ($commands as $command) {
            $result = $executer->execute($command);
            $executedCommands[] = $result;

            if (0 !== $result['error_code']) {
                break;
            }
        }

        return $this->render(
            'CoreSphereConsoleBundle:Console:result.' . $request->getRequestFormat() . '.twig',
            ['commands' => $executedCommands]
        );
    }
}
