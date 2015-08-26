<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Tests\Routing;

use CoreSphere\ConsoleBundle\Routing\Loader;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class LoaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Loader
     */
    private $loader;

    protected function setUp()
    {
        $this->loader = new Loader(new YamlFileLoader(new FileLocator()));
    }

    public function testLoading()
    {
        $routes = $this->loader->load(null, null);
        $this->assertInstanceOf(RouteCollection::class, $routes);

        /** @var Route $route */
        $route = $routes->get('console');
        $this->assertSame('/_console', $route->getPath());
        $this->assertSame('coresphere_console.controller:consoleAction', $route->getDefault('_controller'));

        /** @var Route $route */
        $route = $routes->get('console_exec');
        $this->assertSame('/_console/commands.{_format}', $route->getPath());
        $this->assertSame('coresphere_console.controller:execAction', $route->getDefault('_controller'));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports(null, 'extra'));
        $this->assertFalse($this->loader->supports(null, 'other'));
    }

    public function testResolver()
    {
        $this->assertNull($this->loader->getResolver());

        $resolverMock = $this->prophesize(LoaderResolverInterface::class);
        $this->loader->setResolver($resolverMock->reveal());
    }
}
