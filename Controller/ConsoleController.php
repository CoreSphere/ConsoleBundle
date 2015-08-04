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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class ConsoleController
 *
 * @package CoreSphere\ConsoleBundle\Controller
 */
class ConsoleController extends Controller
{
    /**
     * Console Action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function consoleAction()
    {
        $application = $this->get('coresphere_console.application');

        return $this->render('CoreSphereConsoleBundle:Console:console.html.twig', array(
            'working_dir'   => getcwd(),
            'environment'   => $application->getKernel()->getEnvironment(),
            'commands'      => $application->all(),
        ));
    }

    /**
     * Executer Action
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function execAction(Request $request)
    {
        $commands = $request->request->get('commands');
        $commandExecuter = $this->get('coresphere_console.services.command_executer');

        $executedCommands = array();
        foreach ($commands as $command) {

            $result             = $commandExecuter->execute($command);
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
