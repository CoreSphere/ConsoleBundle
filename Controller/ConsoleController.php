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

class ConsoleController extends Controller
{
    public function indexAction()
    {
        chdir($this->container->getParameter('kernel.root_dir') . '/..');

        return $this->render('CoreSphereConsoleBundle:Console:index.html.twig', array(
            'working_dir' => getcwd()
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


        $env = $this;
        $debug = true;

        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(FALSE);
        $application->run($input, $output);

        return $this->render('CoreSphereConsoleBundle:Console:result.' . $_format . '.twig', array(
            'input' => $command,
            'output' => $output->getOutput(),
        ));
    }
}
