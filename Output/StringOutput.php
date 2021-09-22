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
 * Collects console output into a string.
 */
class StringOutput extends Output
{
    protected string $buffer = '';

    /**
     * {@inheritdoc}
     */
    public function doWrite($message, $newline)
    {
        $this->buffer .= $message.(true === $newline ? PHP_EOL : '');
    }

    public function getBuffer(): string
    {
        return $this->buffer;
    }
}
