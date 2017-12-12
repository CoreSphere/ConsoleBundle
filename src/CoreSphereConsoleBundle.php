<?php declare(strict_types=1);

namespace CoreSphere\ConsoleBundle;

use CoreSphere\ConsoleBundle\DependencyInjection\Extension\CoreSphereConsoleExtension;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CoreSphereConsoleBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ConfigurationExtensionInterface
    {
        return new CoreSphereConsoleExtension();
    }
}
