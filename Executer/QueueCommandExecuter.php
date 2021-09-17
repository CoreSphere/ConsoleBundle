<?php

declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Executer;

use CoreSphere\ConsoleBundle\Contract\Executer\CommandExecuterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class QueueCommandExecuter implements CommandExecuterInterface
{
    public const QUEUE_FILE_NAME = 'coresphere_console_commands';

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var string
     */
    private $queueFileName;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    public function __construct(KernelInterface $kernel, SessionInterface $session, string $queueDir, TranslatorInterface $translator)
    {
        $this->kernel = $kernel;
        $this->session = $session;
        $this->queueFileName = self::getQueueFileName($queueDir);
        $this->translator = $translator;
    }

    public static function getQueueFileName(string $dir): string
    {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException("Could not create $dir directory.");
        }

        return $dir . '/' . self::QUEUE_FILE_NAME . '.dump';
    }

    public static function getCommandDumpFileName(string $dir, string $id): string
    {
        return $dir . '/' . self::QUEUE_FILE_NAME . '_' . $id . '.dump';
    }

    public function execute(string $command, string $workingDir = null, bool $stream = true): array
    {
        $errorCode = 0;
        $sessionId = $this->session->getId();
        try {
            chdir($this->kernel->getRootDir() . '/..');
            $queue = $this->getQueue();
            if (!isset($queue[$sessionId])) {
                $queue[$sessionId] = [];
            }
            if (!in_array($command, $queue[$sessionId], true)) {
                $time = microtime();
                $queue[$sessionId][$time]['command'] = $command;
                $queue[$sessionId][$time]['dir'] = $workingDir;
                $queue[$sessionId][$time]['stream'] = $stream;
                $queue[$sessionId][$time]['sessionId'] = $sessionId;
            }
            $this->updateQueue($queue);
            $output = $this->translator->trans('coresphere_console.loading');
        } catch (\Throwable $e) {
            $errorCode = 1;
            $output = (string) $e;
        }

        return $this->buildResponse($command, $output, $errorCode, $sessionId);
    }

    private function getQueue(): array
    {
        $queue = [];
        if (file_exists($this->queueFileName) && $content = file_get_contents($this->queueFileName)) {
            $queue = unserialize($content);
        }

        return $queue;
    }

    private function updateQueue(array $queue): void
    {
        file_put_contents($this->queueFileName, serialize($queue));
    }

    private function buildResponse(string $command, string $output, int $errorCode, string $id): array
    {
        return [
            'command'     => $command,
            'output'      => $output,
            'environment' => $this->kernel->getEnvironment(),
            'error_code'  => $errorCode,
            'id'          => $id,
        ];
    }
}
