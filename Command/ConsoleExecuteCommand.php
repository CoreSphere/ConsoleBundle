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
    private $commandsDumpFile;

    /**
     * @var AsyncCommandExecuter
     */
    private $executer;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel, CommandExecuter $executer, AsyncCommandExecuter $asyncExecuter)
    {
        $this->commandsDumpFile = QueueCommandExecuter::getQueueFile($kernel->getLogDir());
        $this->kernel = $kernel;
        $this->executer = $executer;
        $this->asyncExecuter = $asyncExecuter;
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
        $loop = $input->getOption('loop');
        $stream = $input->getOption('stream');
        $output->writeln('Listening ...');
        do {
            $sessions = $this->fetchSessionsCommands();
            foreach ($sessions as $sessionId => $commands) {
                foreach ($commands as $i => $command) {
                    $output->writeln("Executing $command");
                    $isForcedStream = self::isForcedStream($command);
                    if ($isForcedStream) {
                        $command = $this->stripAsyncSuffix($command);
                    }
                    $fp = fopen(self::getCommandDumpFile($this->kernel->getLogDir(), $sessionId), 'wb');
                    fwrite($fp, $command."\n");
                    fwrite($fp, $this->kernel->getEnvironment()."\n");
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
                    $this->removeCache($sessionId, $i);
                    $output->writeln("$command done !");
                }
            }
        } while ($loop && $this->delay());
    }

    private function fetchSessionsCommands(): array
    {
        $queue = [];
        if (file_exists($this->commandsDumpFile) && $content = file_get_contents($this->commandsDumpFile)) {
            $queue = unserialize($content);
        }

        return $queue;
    }

    private function removeCache(string $sessionId, string $id): void
    {
        $queue = [];
        if (file_exists($this->commandsDumpFile) && $content = file_get_contents($this->commandsDumpFile)) {
            $queue = unserialize($content);
        }
        if (isset($queue[$sessionId][$id])) {
            unset($queue[$sessionId][$id]);
            if (!$queue[$sessionId]) {
                unset($queue[$sessionId]);
            }
        }
        if (!$queue) {
            if (file_exists($this->commandsDumpFile)) {
                unlink($this->commandsDumpFile);
            }
        } else {
            file_put_contents($this->commandsDumpFile, serialize($queue));
        }
    }

    public static function getCommandDumpFile(string $dir, string $id): string
    {
        return $dir . '/' . QueueCommandExecuter::QUEUE_FILE_NAME . '_' . $id . '.dump';
    }

    private static function isForcedStream(string $command): bool
    {
        return substr($command, -strlen(self::STREAM_SUFFIX)) === self::STREAM_SUFFIX;
    }

    protected function stripAsyncSuffix($command): string
    {
        return str_replace(self::STREAM_SUFFIX, '', $command);
    }
}
