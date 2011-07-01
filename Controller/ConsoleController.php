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
use CoreSphere\ConsoleBundle\Formatter\HtmlOutputFormatterStyle;
use CoreSphere\ConsoleBundle\Formatter\HtmlOutputFormatterDecorator;

class ConsoleController extends Controller
{
    public function indexAction()
    {
        chdir($this->container->getParameter('kernel.root_dir') . '/..');

        $kernel = $this->get('kernel');
        $application = $this->getApplication();

        foreach ($kernel->getBundles() as $bundle) {
            $bundle->registerCommands($application);
        }

        return $this->render('CoreSphereConsoleBundle:Console:index.html.twig', array(
            'working_dir' => getcwd(),
            'environment' => $kernel->getEnvironment(),
            'commands' => $application->all(),
        ));
    }

    public function execAction($_format = 'json')
    {
        chdir($this->container->getParameter('kernel.root_dir') . '/..');

        $request = $this->get('request');
        $command = $request->request->get('command');

        # Cache can not be warmed up as classes can not be redefined during one request
        if(preg_match('/^cache:clear/', $command)) {
            $command .= ' --no-warmup';
        }

        $input = new StringInput($command);
        $input->setInteractive(FALSE);

        $output = new StringOutput();
        $formatter = $output->getFormatter();
        $formatter->setDecorated(true);
        $formatter->setStyle('error',    new HtmlOutputFormatterStyle('white', 'red'));
        $formatter->setStyle('info',     new HtmlOutputFormatterStyle('green'));
        $formatter->setStyle('comment',  new HtmlOutputFormatterStyle('yellow'));
        $formatter->setStyle('question', new HtmlOutputFormatterStyle('black', 'cyan'));
        $output->setFormatter(new HtmlOutputFormatterDecorator($formatter));

        $env = $this;
        $debug = true;

        $application = $this->getApplication($input);
        $application->setAutoExit(FALSE);
        $application->run($input, $output);

        return $this->render('CoreSphereConsoleBundle:Console:result.' . $_format . '.twig', array(
            'input' => $command,
            'output' => $output->getBuffer(),
            'environment' => $this->getKernel($input)->getEnvironment(),
        ));
    }

    protected function getApplication($input = NULL)
    {
        $kernel = $this->getKernel($input);


        return new Application($kernel);
    }

    protected function getKernel($input = NULL)
    {
        $currentKernel = $this->get('kernel');

        if($input === NULL) {
            return $currentKernel;
        }

        $env = $input->getParameterOption(array('--env', '-e'), $currentKernel->getEnvironment());
        $debug = !$input->hasParameterOption(array('--no-debug', ''));

        if($currentKernel->getEnvironment() === $env && $currentKernel->isDebug()===$debug) {
            return $currentKernel;
        }

        return new \AppKernel($env, $debug);
    }
}
