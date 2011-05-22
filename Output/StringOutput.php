<?php

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
