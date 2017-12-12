<?php declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Output;

use Symfony\Component\Console\Output\Output;

/**
 * Collects console output into a string.
 */
final class StringOutput extends Output
{
    /**
     * @var string
     */
    protected $buffer = '';

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
