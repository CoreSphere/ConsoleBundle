<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector AS DataCollectorBase;

class DataCollector extends DataCollectorBase
{

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    }


    public function getName()
    {
        return 'coresphere_console';
    }
}
