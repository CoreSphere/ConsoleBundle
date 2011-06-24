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
        return $this->escape($message);
    }

    protected function escape($message)
    {
        return preg_replace_callback(OutputFormatter::FORMAT_PATTERN, array($this, 'escapeCallback'), $message);
    }

    protected function escapeCallback($match)
    {
        $formated = $this->formatter->format($match[2]);
        if($formated===$match[2]) {
            return $this->formatter->format('<'.$match[1].'>'.htmlspecialchars($match[2], ENT_QUOTES, 'UTF-8').'</'.$match[1].'>');
        } else {
            return $this->formatter->format('<'.$match[1].'>'.$this->escape($match[2]).'</'.$match[1].'>');
        }
    }
}
