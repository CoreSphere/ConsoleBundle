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

use CoreSphere\ConsoleBundle\Formatter\HtmlOutputFormatterStyle;

class HtmlOutputFormatterStyleTest extends \PHPUnit_Framework_TestCase
{

    private $colors = array(
        'black'     => 'rgba(0,0,0,1)',
        'red'       => 'rgba(230,50,50,1)',
        'green'     => 'rgba(50,230,50,1)',
        'yellow'    => 'rgba(230,230,50,1)',
        'blue'      => 'rgba(50,50,230,1)',
        'magenta'   => 'rgba(230,50,150,1)',
        'cyan'      => 'rgba(50,230,230,1)',
        'white'     => 'rgba(250,250,250,1)',
    );

    public function testConstructor()
    {
        $style = new HtmlOutputFormatterStyle('green', 'black', array('bold', 'underscore'));
        $this->assertEquals(
            '<span style="'.
            'color:'.$this->colors['green'].';'.
            'background-color:'.$this->colors['black'].';'.
            'font-weight:bold;'.
            'text-decoration:underline'.
            '">foo</span>', $style->apply('foo'));

        $style = new HtmlOutputFormatterStyle('red', null, array('bold'));
        $this->assertEquals(
            '<span style="'.
            'color:'.$this->colors['red'].';'.
            'font-weight:bold'.
            '">foo</span>', $style->apply('foo'));

        $style = new HtmlOutputFormatterStyle(null, 'white');
        $this->assertEquals(
            '<span style="'.
            'background-color:'.$this->colors['white'].
            '">foo</span>', $style->apply('foo'));
    }




public function testForeground()
{
    $style = new HtmlOutputFormatterStyle();

    $style->setForeground('black');
    $this->assertEquals('<span style="color:'.$this->colors['black'].'">foo</span>', $style->apply('foo'));

    $style->setForeground('blue');
    $this->assertEquals('<span style="color:'.$this->colors['blue'].'">foo</span>', $style->apply('foo'));

    $this->setExpectedException('InvalidArgumentException');
    $style->setForeground('undefined-color');
}




public function testBackground()
{
    $style = new HtmlOutputFormatterStyle();

    $style->setBackground('black');
    $this->assertEquals('<span style="background-color:'.$this->colors['black'].'">foo</span>', $style->apply('foo'));

    $style->setBackground('yellow');
    $this->assertEquals('<span style="background-color:'.$this->colors['yellow'].'">foo</span>', $style->apply('foo'));

    $this->setExpectedException('InvalidArgumentException');
    $style->setBackground('undefined-color');
}


public function testOptions()
{
    $style = new HtmlOutputFormatterStyle();

    $style->setOptions(array('bold', 'underscore'));
    $this->assertEquals('<span style="font-weight:bold;text-decoration:underline">foo</span>', $style->apply('foo'));

    $style->setOption('conceal');
    $this->assertEquals('<span style="font-weight:bold;text-decoration:underline;visibility:hidden">foo</span>', $style->apply('foo'));

    $style->unsetOption('bold');
    $this->assertEquals('<span style="text-decoration:underline;visibility:hidden">foo</span>', $style->apply('foo'));

    $style->setOption('conceal');
    $this->assertEquals('<span style="text-decoration:underline;visibility:hidden">foo</span>', $style->apply('foo'));

    $style->setOptions(array('bold'));
    $this->assertEquals('<span style="font-weight:bold">foo</span>', $style->apply('foo'));
}



}