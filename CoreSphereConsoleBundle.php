<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle;

use CoreSphere\ConsoleBundle\DependencyInjection\Extension\CoreSphereConsoleExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CoreSphereConsoleBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new CoreSphereConsoleExtension();
    }
}
