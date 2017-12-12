<?php declare(strict_types=1);

namespace CoreSphere\ConsoleBundle\Tests\Output;

use CoreSphere\ConsoleBundle\Output\StringOutput;
use PHPUnit\Framework\TestCase;

final class StringOutputTest extends TestCase
{
    public function testWriteRead()
    {
        $output = new StringOutput();
        $text = 'foo';

        $output->write($text);
        $this->assertSame($text, $output->getBuffer());

        $output->write($text, true);
        $this->assertSame($text.$text.PHP_EOL, $output->getBuffer());
    }
}
