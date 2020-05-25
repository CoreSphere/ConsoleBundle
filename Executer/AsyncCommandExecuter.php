<?php

declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Executer;

use CoreSphere\ConsoleBundle\Contract\Executer\CommandExecuterInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class AsyncCommandExecuter implements CommandExecuterInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($command, callable $callback = null)
    {
        $phpPath = $this->getPhpPath();
        $process = new Process([$phpPath, 'app/console', $command, '--env=' . $this->kernel->getEnvironment()]);
        $process->run($callback);

        return [
            'input'       => $command,
            'output'      => $process->getOutput(),
            'environment' => $this->kernel->getEnvironment(),
            'error_code'  => $process->getExitCode(),
        ];
    }

    private function getPhpPath(): string
    {
        if (!$phpPath = (new PhpExecutableFinder())->find()) {
            throw new \RuntimeException('The php executable le could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }
}
