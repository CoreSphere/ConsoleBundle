<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

class ApplicationFactory
{
    public function create(KernelInterface $kernel): Application
    {
        return $this->registerCommandsToApplication(new Application($kernel), $kernel);
    }

    private function registerCommandsToApplication(Application $application, KernelInterface $kernel): Application
    {
        chdir($kernel->getProjectDir());

        foreach ($this->getBundlesFromKernel($kernel) as $bundle) {
            $bundle->registerCommands($application);
        }

        return $application;
    }

    private function getBundlesFromKernel(KernelInterface $kernel): array
    {
        if ($bundles = $kernel->getBundles()) {
            return $bundles;
        }

        return $kernel->registerBundles();
    }
}
