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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use CoreSphere\ConsoleBundle\Executer\CommandExecuter;
use Symfony\Component\HttpFoundation\Request;

class ConsoleController extends Controller
{
    public function consoleAction()
    {
        $kernel = $this->get('kernel');
        $application = new Application($kernel);

        chdir($kernel->getRootDir().'/..');

        foreach ($kernel->getBundles() as $bundle) {
            $bundle->registerCommands($application);
        }

        return $this->render('CoreSphereConsoleBundle:Console:console.html.twig', array(
            'working_dir' => getcwd(),
            'environment' => $kernel->getEnvironment(),
            'commands' => $application->all(),
        ));
    }

    public function execAction(Request $request)
    {
        $executer = new CommandExecuter($this->get('kernel'));
        $commands = $request->request->get('commands');
        $executedCommands = array();

        foreach ($commands as $command) {
            $result = $executer->execute($command);
            $executedCommands[] = $result;

            if (0 !== $result['error_code']) {
                break;
            }
        }

        return $this->render(
            'CoreSphereConsoleBundle:Console:result.' . $request->getRequestFormat() . '.twig',
            array('commands' => $executedCommands)
        );
    }
}
