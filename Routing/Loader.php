<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class Loader implements LoaderInterface
{
    /**
     * @var YamlFileLoader
     */
    private $yamlFileLoader;

    public function __construct(YamlFileLoader $yamlFileLoader)
    {
        $this->yamlFileLoader = $yamlFileLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();
        $collection->addCollection(
            $this->yamlFileLoader->import(__DIR__ . '/../Resources/config/routing.yml')
        );
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'extra' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
    }
}
