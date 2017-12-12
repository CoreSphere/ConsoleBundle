<?php declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Controller;

use CoreSphere\ConsoleBundle\Executer\CommandExecuter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

final class ConsoleController
{
    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var CommandExecuter
     */
    private $commandExecuter;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var string
     */
    private $kernelEnvironment;

    public function __construct(
        string $kernelEnvironment,
        EngineInterface $templating,
        CommandExecuter $commandExecuter,
        Application $application
    ) {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->templating = $templating;
        $this->commandExecuter = $commandExecuter;
        $this->application = $application;
    }

    public function consoleAction()
    {
        return new Response(
            $this->templating->render('CoreSphereConsoleBundle:Console:console.html.twig', [
                'working_dir' => getcwd(),
                'environment' => $this->kernelEnvironment,
                'commands' => $this->application->all(),
            ])
        );
    }

    public function execAction(Request $request): Response
    {
        $commands = $request->request->get('commands');
        $executedCommandsOutput = [];

        foreach ($commands as $command) {
            $result = $this->commandExecuter->execute($command);
            $executedCommandsOutput[] = $result;

            if (0 !== $result['error_code']) {
                break;
            }
        }

        return new Response(
            $this->templating->render(
                'CoreSphereConsoleBundle:Console:result.json.twig',
                ['commands' => $executedCommandsOutput]
            )
        );
    }
}
