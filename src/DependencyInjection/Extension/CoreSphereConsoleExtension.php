<?php declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\DependencyInjection\Extension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

final class CoreSphereConsoleExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->prependExtensionConfig($this->getAlias(), [
            'resource' => '.',
            'type' => 'extra',
        ]);
    }
}
