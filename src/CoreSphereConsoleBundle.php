<?php declare(strict_types=1);

namespace CoreSphere\ConsoleBundle;

use CoreSphere\ConsoleBundle\DependencyInjection\CoreSphereConsoleExtension;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CoreSphereConsoleBundle extends Bundle
{
    public function getContainerExtension(): ConfigurationExtensionInterface
    {
        return new CoreSphereConsoleExtension();
    }
}
