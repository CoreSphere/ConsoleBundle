<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Tests\DataCollector;

use CoreSphere\ConsoleBundle\DataCollector\DataCollector;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DataCollectorTest extends PHPUnit_Framework_TestCase
{
    public function testWhole()
    {
        $dataCollector = new DataCollector();
        $requestMock = $this->prophesize(Request::class);
        $responseMock = $this->prophesize(Response::class);
        $dataCollector->collect($requestMock->reveal(), $responseMock->reveal());

        $this->assertSame('coresphere_console', $dataCollector->getName());
    }
}
