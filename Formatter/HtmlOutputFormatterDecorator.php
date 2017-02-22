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

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

final class HtmlOutputFormatterDecorator implements OutputFormatterInterface
{
    const CLI_COLORS_PATTERN = '/\033\[(([\d+];?)*)m(.*?)\033\[(([\d+];?)*)m/i';

    /**
     * @var string[]
     */
    private $styles = [
        '30' => 'color:rgba(0,0,0,1)',
        '31' => 'color:rgba(230,50,50,1)',
        '32' => 'color:rgba(50,230,50,1)',
        '33' => 'color:rgba(230,230,50,1)',
        '34' => 'color:rgba(50,50,230,1)',
        '35' => 'color:rgba(230,50,150,1)',
        '36' => 'color:rgba(50,230,230,1)',
        '37' => 'color:rgba(250,250,250,1)',
        '40' => 'color:rgba(0,0,0,1)',
        '41' => 'background-color:rgba(230,50,50,1)',
        '42' => 'background-color:rgba(50,230,50,1)',
        '43' => 'background-color:rgba(230,230,50,1)',
        '44' => 'background-color:rgba(50,50,230,1)',
        '45' => 'background-color:rgba(230,50,150,1)',
        '46' => 'background-color:rgba(50,230,230,1)',
        '47' => 'background-color:rgba(250,250,250,1)',
        '1' => 'font-weight:bold',
        '4' => 'text-decoration:underline',
        '8' => 'visibility:hidden',
    ];

    /**
     * @var OutputFormatterInterface
     */
    private $formatter;

    public function __construct(OutputFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated($decorated)
    {
        return $this->formatter->setDecorated($decorated);
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated()
    {
        return $this->formatter->isDecorated();
    }

    /**
     * {@inheritdoc}
     */
    public function setStyle($name, OutputFormatterStyleInterface $style)
    {
        return $this->formatter->setStyle($name, $style);
    }

    /**
     * {@inheritdoc}
     */
    public function hasStyle($name)
    {
        return $this->formatter->hasStyle($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getStyle($name)
    {
        return $this->formatter->getStyle($name);
    }

    /**
     * {@inheritdoc}
     */
    public function format($message)
    {
        if (!$this->isDecorated()) {
            return $message;
        }
        $formatted = $this->formatter->format($message);
        $escaped = htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8');
        $converted = preg_replace_callback(self::CLI_COLORS_PATTERN, function ($matches) {
            return $this->replaceFormat($matches);
        }, $escaped);

        return $converted;
    }

    /**
     * @return string
     */
    private function replaceFormat(array $matches)
    {
        $text = $matches[3];
        $styles = explode(';', $matches[1]);

        $css = array_intersect_key($this->styles, array_flip($styles));

        return sprintf(
            '<span style="%s">%s</span>',
            implode(';', $css),
            $text
        );
    }
}
