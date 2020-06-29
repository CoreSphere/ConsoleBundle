<?php

declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Command;

use CoreSphere\ConsoleBundle\Executer\ProcessCommandExecuter;
use CoreSphere\ConsoleBundle\Executer\QueueCommandExecuter;
use CoreSphere\ConsoleBundle\Executer\SymfonyCommandExecuter;
use CoreSphere\ConsoleBundle\Formatter\HtmlOutputFormatterDecorator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

class ConsoleExecuteCommand extends Command
{
    private const TTL              = 900;

    private const LOG_DATE_FORMAT  = '[Y-m-d H:i:s] ';

    private const OPTION_LOOP      = 'loop';

    private const OPTION_NO_STREAM = 'no-stream';

    public const  OUTPUT_END       = "> END\n";

    /**
     * @var ProcessCommandExecuter
     */
    private $processExecuter;

    /**
     * @var string
     */
    private $commandsQueueFile;

    /**
     * @var ProcessCommandExecuter
     */
    private $symfonyExecuter;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var LockFactory
     */
    private $locker;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $queueDir;

    public function __construct(KernelInterface $kernel, SymfonyCommandExecuter $executer, ProcessCommandExecuter $asyncExecuter, string $queueDir)
    {
        $this->commandsQueueFile = QueueCommandExecuter::getQueueFileName($queueDir);
        $this->kernel = $kernel;
        $this->symfonyExecuter = $executer;
        $this->processExecuter = $asyncExecuter;
        $this->queueDir = $queueDir;
        $this->locker = new LockFactory(new FlockStore());
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('oc:console:execute:commands')
             ->setDescription('execute commands from console dump')
             ->addOption(self::OPTION_LOOP, 'l', InputOption::VALUE_NONE)
             ->addOption(self::OPTION_NO_STREAM, 's', InputOption::VALUE_NONE);
    }

    private function delay(): bool
    {
        usleep(500000);

        return true;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $start = time();
        $this->input = $input;
        $this->output = $output;
        $lock = $this->locker->createLock($this->getName());
        if (!$lock->acquire()) {
            return;
        }
        $loop = $input->getOption(self::OPTION_LOOP);
        if ($loop) {
            $output->writeln('Listening ...');
        }
        try {
            do {
                $sessions = $this->fetchSessionsCommands();
                foreach ($sessions as $sessionId => $commands) {
                    foreach ($commands as $i => $command) {
                        $output->writeln(date(self::LOG_DATE_FORMAT) . "Start {$command['command']}");
                        $this->executeCommand($command);
                        $output->writeln(date(self::LOG_DATE_FORMAT) . "End {$command['command']}");
                        $this->removeCache($sessionId, $i);
                    }
                }
            } while ($loop && $this->delay() && !$this->isExpired($start));
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

    private function clearQueue(): void
    {
        if (file_exists($this->commandsQueueFile)) {
            unlink($this->commandsQueueFile);
        }
    }

    private function executeCommand(array $command): void
    {
        $workingDir = $command['dir'];
        $commandLine = $command['command'];
        $stream = $command['stream'] && !$this->input->getOption(self::OPTION_NO_STREAM);
        $sessionId = $command['sessionId'];

        $fp = fopen(QueueCommandExecuter::getCommandDumpFileName($this->queueDir, $sessionId), 'wb');
        fwrite($fp, $commandLine . "\n");
        fwrite($fp, $this->kernel->getEnvironment() . "\n");
        try {
            if ($stream) {
                $this->processExecuter->execute(
                    $commandLine,
                    $workingDir,
                    true,
                    static function ($type, $buffer) use ($fp) {
                        $formatter = new HtmlOutputFormatterDecorator(new OutputFormatter(true));
                        $buffer = $formatter->format($buffer);
                        fwrite($fp, $buffer);
                    }
                );
            } else {
                $result = $this->symfonyExecuter->execute($commandLine, $workingDir);
                fwrite($fp, $result['output']);
            }
        } catch (\Throwable $e) {
            fwrite($fp, $e->getMessage());
            $this->output->writeln($e);
        }
        fwrite($fp, self::OUTPUT_END);
        fclose($fp);
    }

    private function isExpired(int $start): bool
    {
        return time() > $start + self::TTL;
    }
}
