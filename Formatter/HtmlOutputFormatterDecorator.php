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

    private $htmlPattern;


    public function __construct($formatter)
    {
        $this->formatter = $formatter;
        $this->htmlPattern = htmlspecialchars(OutputFormatter::FORMAT_PATTERN, ENT_QUOTES, 'UTF-8');
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
        $escaped = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return preg_replace_callback($this->htmlPattern, array($this, 'doFormat'), $escaped);
    }

    protected function doFormat($matches)
    {
        $input = sprintf("%s<%s%s>%s", $matches[1], $matches[2], $matches[3], $matches[4]);

        if(($formatted = $this->formatter->format($input)) !== $this->formatter->format(sprintf('\\%s', $input))) {
            $output = $formatted;
        } else {
            $output = substr($this->formatter->format(sprintf('<>%s', $matches[0])), 2);
        }

        return $output;
    }
}
