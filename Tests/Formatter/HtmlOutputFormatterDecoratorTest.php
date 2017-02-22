<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Tests\Formatter;

use CoreSphere\ConsoleBundle\Formatter\HtmlOutputFormatterDecorator;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

final class HtmlOutputFormatterDecoratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var HtmlOutputFormatterDecorator
     */
    private $decoratedFormatter;

    protected function setUp()
    {
        $this->decoratedFormatter = new HtmlOutputFormatterDecorator(new OutputFormatter(true));
    }

    public function testEscapingOutput()
    {
        $this->decoratedFormatter->setStyle('error', new OutputFormatterStyle('white', 'red'));
        $this->decoratedFormatter->setStyle('info', new OutputFormatterStyle('green'));
        $this->decoratedFormatter->setStyle('comment', new OutputFormatterStyle('yellow'));
        $this->decoratedFormatter->setStyle('question', new OutputFormatterStyle('black', 'cyan'));

        $this->assertSame(
            'a&lt;script&gt;evil();&lt;/script&gt;a', $this->decoratedFormatter->format(
                'a<script>evil();</script>a'
            )
        );

        $this->assertSame(
            '&lt;script&gt;<span style="color:rgba(50,230,50,1)">evil();</span>&lt;/script&gt;', $this->decoratedFormatter->format(
                '<script><info>evil();</info></script>'
            )
        );

        $this->assertSame(
            '<span style="color:rgba(50,230,50,1)">a</span>'.
            '<span style="color:rgba(50,230,50,1)">&lt;script&gt;</span>'.
            '<span style="color:rgba(250,250,250,1);background-color:rgba(230,50,50,1)">evil();</span>'.
            '<span style="color:rgba(50,230,50,1)">&lt;/script&gt;</span>', $this->decoratedFormatter->format(
                '<info>a<script><error>evil();</error></script>'
            )
        );

        $this->assertSame(
            '<span style="color:rgba(50,230,50,1)">a&amp;lt;</span>'.
            '<span style="color:rgba(50,230,50,1)">&lt;script&gt;</span>'.
            '<span style="color:rgba(50,230,50,1)">evil();</span>'.
            '<span style="color:rgba(50,230,50,1)">&lt;/script&gt;</span>', $this->decoratedFormatter->format(
                '<info>a&lt;<script><info>evil();</info></script>'
            )
        );
    }

    public function testDecorated()
    {
        $this->assertTrue($this->decoratedFormatter->isDecorated());

        $this->decoratedFormatter->setDecorated(false);
        $this->assertFalse($this->decoratedFormatter->isDecorated());
    }

    public function testUndecorated()
    {
        $this->decoratedFormatter->setStyle('info', new OutputFormatterStyle('green'));

        $this->decoratedFormatter->setDecorated(false);

        $this->assertSame(
            '<info>Do not change</info>',
            $this->decoratedFormatter->format(
                '<info>Do not change</info>'
            )
        );
    }

    public function testStyle()
    {
        $this->assertFalse($this->decoratedFormatter->hasStyle('nonExisting'));

        $outputFormatterStyle = new OutputFormatterStyle('blue');
        $this->decoratedFormatter->setStyle('blue', $outputFormatterStyle);
        $this->assertTrue($this->decoratedFormatter->hasStyle('blue'));

        $this->assertSame($outputFormatterStyle, $this->decoratedFormatter->getStyle('blue'));
    }
}
