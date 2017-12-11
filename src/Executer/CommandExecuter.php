<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Executer;

use CoreSphere\ConsoleBundle\Contract\Executer\CommandExecuterInterface;
use CoreSphere\ConsoleBundle\Output\StringOutput;
use CoreSphere\ConsoleBundle\Formatter\HtmlOutputFormatterDecorator;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Console\Application as FrameworkConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\HttpKernel\KernelInterface;

final class CommandExecuter implements CommandExecuterInterface
{
    /**
     * @var KernelInterface
     */
    private $baseKernel;

    public function __construct(KernelInterface $baseKernel)
    {
        $this->baseKernel = $baseKernel;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($commandString)
    {
        $input = new StringInput($commandString);
        $output = new StringOutput();

        $application = $this->getApplication($input);
        $formatter = $output->getFormatter();
        $kernel = $application->getKernel();

        chdir($kernel->getRootDir().'/..');

        $input->setInteractive(false);
        $formatter->setDecorated(true);
        $output->setFormatter(new HtmlOutputFormatterDecorator($formatter));
        $application->setAutoExit(false);

        $errorCode = $application->run($input, $output);

        return [
            'input' => $commandString,
            'output' => $output->getBuffer(),
            'environment' => $kernel->getEnvironment(),
            'error_code' => $errorCode,
        ];
    }

    /**
     * @return FrameworkConsoleApplication
     */
    private function getApplication(InputInterface $input)
    {
        $kernel = $this->getKernel($input);

        return new FrameworkConsoleApplication($kernel);
    }

    /**
     * @return KernelInterface
     */
    private function getKernel(InputInterface $input)
    {
        $env = $input->getParameterOption(['--env', '-e'], $this->baseKernel->getEnvironment());
        $debug = !$input->hasParameterOption(['--no-debug', '']);

        if ($env === $this->baseKernel->getEnvironment() && $debug === $this->baseKernel->isDebug()) {
            return $this->baseKernel;
        }

        $kernelClass = new ReflectionClass($this->baseKernel);

        return $kernelClass->newInstance($env, $debug);
    }
}
