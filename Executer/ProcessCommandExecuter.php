<?php

declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Executer;

use CoreSphere\ConsoleBundle\Contract\Executer\CommandExecuterInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class ProcessCommandExecuter implements CommandExecuterInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function execute(string $command, string $workingDir = null, bool $stream = true, callable $callback = null): array
    {
        $phpPath = $this->getPhpPath();
        $process = Process::fromShellCommandline("$phpPath app/console $command --env={$this->kernel->getEnvironment()} --ansi", $workingDir);
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
