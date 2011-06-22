<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Output;

use Symfony\Component\Console\Output\Output;


/**
 * StringOutput
 *
 * Collects console output into a string.
 */
class StringOutput extends Output
{
    protected $output = '';

    public function doWrite($message, $newline)
    {
        $this->output .= $message . ($newline===TRUE ? PHP_EOL : '');
    }

    public function getOutput() {
        return $this->output;
    }
}
