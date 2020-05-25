<?php

declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Command;

use CoreSphere\ConsoleBundle\Executer\AsyncCommandExecuter;
use CoreSphere\ConsoleBundle\Executer\CommandExecuter;
use CoreSphere\ConsoleBundle\Executer\QueueCommandExecuter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

class ConsoleExecuteCommand extends Command
{
    public const OUTPUT_END    = "> END\n";

    public const STREAM_SUFFIX = '&stream';

    /**
     * @var AsyncCommandExecuter
     */
    private $asyncExecuter;

    /**
     * @var string
     */
    private $commandsQueueFile;

    /**
     * @var AsyncCommandExecuter
     */
    private $executer;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var LockFactory
     */
    private $locker;

    /**
     * @var string
     */
    private $queueDir;

    public function __construct(KernelInterface $kernel, CommandExecuter $executer, AsyncCommandExecuter $asyncExecuter, string $queueDir)
    {
        $this->commandsQueueFile = QueueCommandExecuter::getQueueFileName($queueDir);
        $this->kernel = $kernel;
        $this->executer = $executer;
        $this->asyncExecuter = $asyncExecuter;
        $this->queueDir = $queueDir;
        $this->locker = new LockFactory(new FlockStore());
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('oc:console:execute:commands')
             ->setDescription('execute commands from console dump')
             ->addOption('loop', 'l', InputOption::VALUE_NONE)
             ->addOption('stream', 's', InputOption::VALUE_NONE);
    }

    private function delay(): bool
    {
        usleep(500000);

        return true;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->locker->createLock($this->getName());
        if (!$lock->acquire()) {
            return;
        }
        $loop = $input->getOption('loop');
        if ($loop) {
            $output->writeln('Listening ...');
        }
        $stream = $input->getOption('stream');
        try {
            do {
                $sessions = $this->fetchSessionsCommands();
                foreach ($sessions as $sessionId => $commands) {
                    foreach ($commands as $i => $command) {
                        try {
                            $output->writeln("Executing $command");
                            $command = $this->executeCommand($command, $sessionId, $stream);
                            $output->writeln("$command done !");
                        } catch (\Throwable $e) {
                            $output->writeln($e);
                        } finally {
                            $this->removeCache($sessionId, $i);
                        }
                    }
                }
            } while ($loop && $this->delay());
        } catch (\Throwable $e) {
            $output->writeln($e);
        } finally {
            $lock->release();
        }
    }

    private function fetchSessionsCommands(): array
    {
        $queue = [];
        if (file_exists($this->commandsQueueFile) && $content = file_get_contents($this->commandsQueueFile)) {
            $queue = unserialize($content);
        }

        return $queue;
    }

    private function removeCache(string $sessionId, string $id): void
    {
        $queue = [];
        if (file_exists($this->commandsQueueFile) && $content = file_get_contents($this->commandsQueueFile)) {
            $queue = unserialize($content);
        }
        if (isset($queue[$sessionId][$id])) {
            unset($queue[$sessionId][$id]);
            if (!$queue[$sessionId]) {
                unset($queue[$sessionId]);
            }
        }
        if (!$queue) {
            $this->clearQueue();
        } else {
            file_put_contents($this->commandsQueueFile, serialize($queue));
        }
    }

    private static function isForcedStream(string $command): bool
    {
        return substr($command, -strlen(self::STREAM_SUFFIX)) === self::STREAM_SUFFIX;
    }

    protected function stripAsyncSuffix($command): string
    {
        return str_replace(self::STREAM_SUFFIX, '', $command);
    }

    private function clearQueue(): void
    {
        if (file_exists($this->commandsQueueFile)) {
            unlink($this->commandsQueueFile);
        }
    }

    private function executeCommand($command, $sessionId, $stream): string
    {
        $isForcedStream = self::isForcedStream($command);
        if ($isForcedStream) {
            $command = $this->stripAsyncSuffix($command);
        }
        $fp = fopen(QueueCommandExecuter::getCommandDumpFileName($this->queueDir, $sessionId), 'wb');
        fwrite($fp, $command . "\n");
        fwrite($fp, $this->kernel->getEnvironment() . "\n");
        if ($stream || $isForcedStream) {
            $this->asyncExecuter->execute(
                $command,
                static function ($type, $buffer) use ($fp) {
                    fwrite($fp, $buffer);
                }
            );
        } else {
            $result = $this->executer->execute($command);
            fwrite($fp, $result['output']);
        }
        fwrite($fp, self::OUTPUT_END);
        fclose($fp);

        return $command;
    }
}
