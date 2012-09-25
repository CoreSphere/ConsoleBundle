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
use CoreSphere\ConsoleBundle\Formatter\HtmlOutputFormatterStyle;

use Symfony\Component\Console\Formatter\OutputFormatter;

class HtmlOutputFormatterDecoratorTest extends \PHPUnit_Framework_TestCase
{

    public function testEscapingOutput()
    {
        $raw_formatter = new OutputFormatter(true);
        $decorated_formatter = new HtmlOutputFormatterDecorator($raw_formatter);

        $decorated_formatter->setStyle('error',    new HtmlOutputFormatterStyle('white', 'red'));
        $decorated_formatter->setStyle('info',     new HtmlOutputFormatterStyle('green'));
        $decorated_formatter->setStyle('comment',  new HtmlOutputFormatterStyle('yellow'));
        $decorated_formatter->setStyle('question', new HtmlOutputFormatterStyle('black', 'cyan'));

        $this->assertSame(
            'a&lt;script&gt;evil();&lt;/script&gt;a', $decorated_formatter->format(
                'a<script>evil();</script>a'
            )
        );

        $this->assertSame(
            '&lt;script&gt;<span style="color:rgba(50,230,50,1)">evil();</span>&lt;/script&gt;', $decorated_formatter->format(
                '<script><info>evil();</info></script>'
            )
        );

        $this->assertSame(
            '<span style="color:rgba(50,230,50,1)">a</span>'.
            '<span style="color:rgba(50,230,50,1)">&lt;script&gt;</span>'.
            '<span style="color:rgba(250,250,250,1);background-color:rgba(230,50,50,1)">evil();</span>'.
            '<span style="color:rgba(50,230,50,1)">&lt;/script&gt;</span>'
            , $decorated_formatter->format(
                '<info>a<script><error>evil();</error></script>'
            )
        );

        $this->assertSame(
            '<span style="color:rgba(50,230,50,1)">a&amp;lt;</span>'.
            '<span style="color:rgba(50,230,50,1)">&lt;script&gt;</span>'.
            '<span style="color:rgba(50,230,50,1)">evil();</span>'.
            '<span style="color:rgba(50,230,50,1)">&lt;/script&gt;</span>'
            , $decorated_formatter->format(
                '<info>a&lt;<script><info>evil();</info></script>'
            )
        );
    }

}
