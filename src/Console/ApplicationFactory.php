<?php declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

class ApplicationFactory
{
    public function create(KernelInterface $kernel): Application
    {
        return new Application($kernel);
    }

    // @todo
    public function createFromConfig()
    {

    }
}
