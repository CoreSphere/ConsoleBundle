<?php

declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Executer;

use CoreSphere\ConsoleBundle\Contract\Executer\CommandExecuterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class QueueCommandExecuter implements CommandExecuterInterface
{
    public const QUEUE_FILE_NAME = 'coresphere_console_commands';

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(KernelInterface $kernel, SessionInterface $session)
    {
        $this->kernel = $kernel;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($command)
    {
        $errorCode = 0;
        $sessionId = $this->session->getId();
        try {
            chdir($this->kernel->getRootDir() . '/..');
            $commandsQueueFile = self::getQueueFile($this->kernel->getLogDir());
            $queue = $this->getQueue($commandsQueueFile);
            if (!isset($queue[$sessionId])) {
                $queue[$sessionId] = [];
            }
            if (!in_array($command, $queue[$sessionId], true)) {
                $queue[$sessionId][microtime()] = $command;
            }
            $this->updateQueue($commandsQueueFile, $queue);
            $output = 'Queued...';
        } catch (\Throwable $e) {
            $errorCode = 1;
            $output = (string) $e;
        }

        return $this->buildResponse($command, $output, $errorCode);
    }

    public static function getQueueFile(string $dir): string
    {
        return $dir . '/' . self::QUEUE_FILE_NAME . '.dump';
    }

    private function getQueue(string $commandsQueueFile): array
    {
        $queue = [];
        if (file_exists($commandsQueueFile) && $content = file_get_contents($commandsQueueFile)) {
            $queue = unserialize($content);
        }

        return $queue;
    }

    private function updateQueue(string $commandsQueueFile, array $queue): void
    {
        file_put_contents($commandsQueueFile, serialize($queue));
    }

    private function buildResponse(string $command, string $output, int $errorCode): array
    {
        return [
            'input'       => $command,
            'output'      => $output,
            'environment' => $this->kernel->getEnvironment(),
            'error_code'  => $errorCode,
        ];
    }
}
