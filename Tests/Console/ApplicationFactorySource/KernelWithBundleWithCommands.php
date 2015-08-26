<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Tests\Console\ApplicationFactorySource;

use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class KernelWithBundleWithCommands extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            new DoctrineMigrationsBundle()
        ];

        var_dump($this->getEnvironment());
        die;

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new DoctrineFixturesBundle();
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }
}
