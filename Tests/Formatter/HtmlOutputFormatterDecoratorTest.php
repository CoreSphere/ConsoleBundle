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
            '<span style="color:rgba(50,230,50,1)">a&lt;script&gt;&lt;info&gt;evil();</span>&lt;/script&gt;', $decorated_formatter->format(
                '<info>a<script><info>evil();</info></script>'
            )
        );

        $this->assertSame(
            '<span style="color:rgba(50,230,50,1)">a&amp;lt;&lt;script&gt;&lt;info&gt;evil();</span>&lt;/script&gt;', $decorated_formatter->format(
                '<info>a&lt;<script><info>evil();</info></script>'
            )
        );
    }

}