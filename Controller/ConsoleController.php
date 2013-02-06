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
use Symfony\Component\Console\Input\StringInput;

use CoreSphere\ConsoleBundle\Output\StringOutput;
use CoreSphere\ConsoleBundle\Formatter\HtmlOutputFormatterDecorator;
use Symfony\Component\HttpFoundation\Request;

class ConsoleController extends Controller
{
    public function consoleAction()
    {
        chdir($this->container->getParameter('kernel.root_dir') . '/..');

        $kernel = $this->get('kernel');
        $application = $this->getApplication();

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
        chdir($this->container->getParameter('kernel.root_dir') . '/..');

        $commands = $request->request->get('commands');

        $cmds = array();
        foreach ($commands as $command) {
            $cmd = $this->executeCommand($command);
            $cmds[] = $cmd;
            if (0 !== $cmd['error_code']) {
                break;
            }
        }

        return $this->render(
            'CoreSphereConsoleBundle:Console:result.' . $request->getRequestFormat() . '.twig',
            array('commands' => $cmds)
        );
    }

    protected function executeCommand($command)
    {
        // Cache can not be warmed up as classes can not be redefined during one request
        if(preg_match('/^cache:clear/', $command)) {
            $command .= ' --no-warmup';
        }

        $input = new StringInput($command);
        $input->setInteractive(false);

        $output = new StringOutput();
        $formatter = $output->getFormatter();
        $formatter->setDecorated(true);
        $output->setFormatter(new HtmlOutputFormatterDecorator($formatter));

        $application = $this->getApplication($input);
        $application->setAutoExit(false);
        $errorCode = $application->run($input, $output);

        return array(
            'input'       => $command,
            'output'      => $output->getBuffer(),
            'environment' => $this->getKernel($input)->getEnvironment(),
            'error_code'  => $errorCode
        );
    }

    protected function getApplication($input = null)
    {
        $kernel = $this->getKernel($input);

        return new Application($kernel);
    }

    protected function getKernel($input = null)
    {
        $currentKernel = $this->get('kernel');

        if($input === null) {
            return $currentKernel;
        }

        $env = $input->getParameterOption(array('--env', '-e'), $currentKernel->getEnvironment());
        $debug = !$input->hasParameterOption(array('--no-debug', ''));

        if($currentKernel->getEnvironment() === $env && $currentKernel->isDebug()===$debug) {
            return $currentKernel;
        }

        $kernelClass = new \ReflectionClass($currentKernel);

        return $kernelClass->newInstance($env, $debug);
    }
}
