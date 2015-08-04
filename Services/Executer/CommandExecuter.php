<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Services\Executer;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\StringInput;
use CoreSphere\ConsoleBundle\Output\StringOutput;
use CoreSphere\ConsoleBundle\Application\Application;
use CoreSphere\ConsoleBundle\Formatter\HtmlOutputFormatterDecorator;

/**
 * Class CommandExecuter
 *
 * Takes a string to execute as console command.
 *
 * @package CoreSphere\ConsoleBundle\Services\Executer
 */
class CommandExecuter
{
    /**
     * @var array
     */
    private $config = array();

    /**
     * @var KernelInterface
     */
    protected $baseKernel;

    /**
     * Constructor
     *
     * @param KernelInterface $baseKernel
     * @param array $config
     */
    public function __construct(KernelInterface $baseKernel, $config = array())
    {
        $this->baseKernel = $baseKernel;
        $this->config     = $config;
    }

    /**
     * Get Application
     *
     * @param null $input
     * @return Application
     */
    protected function getApplication($input = null)
    {
        $kernel = $this->getKernel($input);

        return new Application($kernel, $this->config);
    }

    /**
     * Get Kernel
     *
     * @param null $input
     * @return object
     */
    protected function getKernel($input = null)
    {
        if ($input === null) {
            return $this->baseKernel;
        }

        $env    = $input->getParameterOption(array('--env', '-e'), $this->baseKernel->getEnvironment());
        $debug  = !$input->hasParameterOption(array('--no-debug', ''));

        if ($this->baseKernel->getEnvironment() === $env && $this->baseKernel->isDebug() === $debug) {
            return $this->baseKernel;
        }

        $kernelClass = new \ReflectionClass($this->baseKernel);

        return $kernelClass->newInstance($env, $debug);
    }

    /**
     * Execute
     *
     * @param $commandString
     * @return array
     * @throws \Exception
     */
    public function execute($commandString)
    {
        $input  = new StringInput($commandString);
        $output = new StringOutput();

        $application = $this->getApplication($input);
        $formatter   = $output->getFormatter();
        $kernel      = $application->getKernel();

        chdir($kernel->getRootDir().'/..');

        $input->setInteractive(false);
        $formatter->setDecorated(true);
        $output->setFormatter(new HtmlOutputFormatterDecorator($formatter));
        $application->setAutoExit(false);

        ob_start();
        $errorCode = $application->run($input, $output);
        $result = $output->getBuffer() || ob_get_contents();
        ob_end_clean();

        return array(
            'input'       => $commandString,
            'output'      => $output->getBuffer(),
            'environment' => $kernel->getEnvironment(),
            'error_code'  => $errorCode
        );
    }
}
