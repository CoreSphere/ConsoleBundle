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
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

class ApplicationFactory
{
    /**
     * @return Application
     */
    public function create(KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application = $this->registerCommandsToApplication($application, $kernel);

        return $application;
    }

    /**
     * @return Application
     */
    private function registerCommandsToApplication(Application $application, KernelInterface $kernel)
    {
        chdir($kernel->getRootDir().'/..');

        foreach ($this->getBundlesFromKernel($kernel) as $bundle) {
            $bundle->registerCommands($application);
        }

        return $application;
    }

    /**
     * @return Bundle[]
     */
    private function getBundlesFromKernel(KernelInterface $kernel)
    {
        if ($bundles = $kernel->getBundles()) {
            return $bundles;
        }

        return $kernel->registerBundles();
    }
}
