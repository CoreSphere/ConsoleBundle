<?php

namespace CoreSphere\ConsoleBundle\DependencyInjection;

use CoreSphere\ConsoleBundle\Application\Application;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class CoreSphereConsoleExtension
 *
 * @package CoreSphere\ConsoleBundle\DependencyInjection
 */
class CoreSphereConsoleExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Load Yaml File
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // Process Configurations
        $config = $this->processConfiguration(new Configuration(), $configs);

        // Check Filtering
        if (isset($config['filtering']) && (count($config['filtering']['whitelist'])  > 0 && count($config['filtering']['blacklist']) > 0)) {
            throw new \LogicException('Use the whitelist or blacklist configuration.');
        }

        // Set Container Parameter
        $container->setParameter('coresphere_console.config', $config);
    }
}
