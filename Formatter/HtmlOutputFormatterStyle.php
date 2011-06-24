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

use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;


class HtmlOutputFormatterStyle implements OutputFormatterStyleInterface
{
    static private $colors = array(
        'black'     => 'rgba(0,0,0,1)',
        'red'       => 'rgba(230,50,50,1)',
        'green'     => 'rgba(50,230,50,1)',
        'yellow'    => 'rgba(230,230,50,1)',
        'blue'      => 'rgba(50,50,230,1)',
        'magenta'   => 'rgba(230,50,150,1)',
        'cyan'      => 'rgba(50,230,230,1)',
        'white'     => 'rgba(250,250,250,1)',
    );

    static private $availableOptions = array(
        'bold'          => 'font-weight:bold',
        'underscore'    => 'text-decoration:underline',
        'conceal'       => 'visibility:hidden',
    );

    private $foreground;
    private $background;
    private $options = array();

    /**
     * Initializes output formatter style.
     *
     * @param   string  $foreground     style foreground color name
     * @param   string  $background     style background color name
     * @param   array   $options        style options
     */
    public function __construct($foreground = null, $background = null, array $options = array())
    {

        if (null !== $foreground) {
            $this->setForeground($foreground);
        }
        if (null !== $background) {
            $this->setBackground($background);
        }
        if (count($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Sets style foreground color.
     *
     * @param   string  $color  color name
     */
    public function setForeground($color = null)
    {
        if (null === $color) {
            $this->foreground = null;

            return;
        }

        if (!isset(static::$colors[$color])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid foreground color specified: "%s". Expected one of (%s)',
                $color,
                implode(', ', array_keys(static::$colors))
            ));
        }

        $this->foreground = static::$colors[$color];
    }

    /**
     * Sets style background color.
     *
     * @param   string  $color  color name
     */
    public function setBackground($color = null)
    {
        if (null === $color) {
            $this->background = null;

            return;
        }

        if (!isset(static::$colors[$color])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid background color specified: "%s". Expected one of (%s)',
                $color,
                implode(', ', array_keys(static::$colors))
            ));
        }

        $this->background = static::$colors[$color];
    }

    /**
     * Sets some specific style option.
     *
     * @param   string  $option     option name
     */
    public function setOption($option)
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid option specified: "%s". Expected one of (%s)',
                $option,
                implode(', ', array_keys(static::$availableOptions))
            ));
        }

        if (false === array_search(static::$availableOptions[$option], $this->options)) {
            $this->options[] = static::$availableOptions[$option];
        }
    }

    /**
     * Unsets some specific style option.
     *
     * @param   string  $option     option name
     */
    public function unsetOption($option)
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid option specified: "%s". Expected one of (%s)',
                $option,
                implode(', ', array_keys(static::$availableOptions))
            ));
        }

        $pos = array_search(static::$availableOptions[$option], $this->options);
        if (false !== $pos) {
            unset($this->options[$pos]);
        }
    }

    /**
     * Set multiple style options at once.
     *
     * @param   array   $options
     */
    public function setOptions(array $options)
    {
        $this->options = array();

        foreach ($options as $option) {
            $this->setOption($option);
        }
    }

    /**
     * Applies the style to a given text.
     *
     * @param string $text The text to style
     *
     * @return string
     */
    public function apply($text)
    {
        $styles = array();

        if (null !== $this->foreground) {
            $styles[] = "color:{$this->foreground}";
        }
        if (null !== $this->background) {
            $styles[] = "background-color:{$this->background}";
        }
        if (count($this->options)) {
            $styles = array_merge($styles, $this->options);
        }

        return sprintf('<span style="%s">%s</span>', implode(';', $styles), $text);
    }
}
