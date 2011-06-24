<?php

/*
 * This file is part of the CoreSphereConsoleBundle.
 *
 * (c) Laszlo Korte <me@laszlokorte.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CoreSphere\ConsoleBundle\Formatter;


use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class HtmlOutputFormatterDecorator implements OutputFormatterInterface
{
    private $formatter;

    public function __construct($formatter)
    {
        $this->formatter = $formatter;
    }


    function setDecorated($decorated)
    {
        return $this->formatter->setDecorated($decorated);
    }


    function isDecorated(){
        return $this->formatter->isDecorated();
    }


    function setStyle($name, OutputFormatterStyleInterface $style)
    {
        return $this->formatter->setStyle($name, $style);
    }


    function hasStyle($name)
    {
        return $this->formatter->hasStyle($name);
    }


    function getStyle($name)
    {
        return $this->formatter->getStyle($name);
    }


    function format($message)
    {
        return $this->formatter->format($this->unescape(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')));
    }

    protected function unescape($message)
    {
        $new_pattern = htmlspecialchars(OutputFormatter::FORMAT_PATTERN, ENT_QUOTES, 'UTF-8');
        return preg_replace_callback($new_pattern, array($this, 'doFormat'), $message);
    }

    protected function doFormat($matches)
    {
        $input = "<{$matches[1]}>{$this->unescape($matches[2])}</{$matches[1]}>";

        if($input === ($output=$this->formatter->format($input))) {
            return "&lt;{$matches[1]}&gt;{$this->unescape($matches[2])}&lt;/{$matches[1]}&gt;";
        } else {
            return $output;
        }
    }
}
