<?php
/**
 * Copyright 2003-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Cli
 */

/**
 * Horde_Cli API for basic command-line functionality/checks.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2003-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Cli
 * @since     Horde_Cli 2.2.0
 */
class Horde_Cli_Color
{
    /**
     * No color formatting.
     */
    const FORMAT_NONE = 0;

    /**
     * xterm compatible color formatting.
     */
    const FORMAT_XTERM = 1;

    /**
     * VT100 compatible color formatting.
     */
    const FORMAT_VT100 = 2;

    /**
     * HTML compatible color formatting.
     */
    const FORMAT_HTML = 3;

    /**
     * The color formatting being used.
     *
     * One of the FORMAT_* constants.
     */
    protected $_format = self::FORMAT_NONE;

    /**
     * Constructor.
     *
     * @param integer $format  The color format to use. One of the FORMAT_*
     *                         constants. Automatically detected by default.
     */
    public function __construct($format = null)
    {
        if (!is_null($format)) {
            $this->_format = $format;
            return;
        }

        if (Horde_Cli::runningFromCli()) {
            $term = getenv('TERM');
            if ($term) {
                if (preg_match('/^(xterm|vt220|linux)/', $term)) {
                    $this->_format = self::FORMAT_XTERM;
                } elseif (preg_match('/^vt100/', $term)) {
                    $this->_format = self::FORMAT_VT100;
                }
            }
        } else {
            $this->_format = self::FORMAT_HTML;
        }
    }

    /**
     * Returns a bold version of $text.
     *
     * @param string $text  The text to bold.
     *
     * @return string  The bolded text.
     */
    public function bold($text)
    {
        $bold_start = $bold_end = '';
        switch ($this->_format) {
        case self::FORMAT_XTERM:
        case self::FORMAT_VT100:
            $bold_start = "\x1b[1m";
            $bold_end   = "\x1b[0m";
            break;
        case self::FORMAT_HTML:
            $bold_start = '<strong>';
            $bold_end   = '</strong>';
            break;
        }
        return $bold_start . $text . $bold_end;
    }

    /**
     * Returns a colored version of $text.
     *
     * @param string $color  The color to use. One of:
     *                        - normal
     *                        - black
     *                        - bold
     *                        - red
     *                        - green
     *                        - brown
     *                        - blue
     *                        - magenta
     *                        - cyan
     *                        - lightgray
     *                        - white
     *                        - darkgray
     *                        - lightred
     *                        - lightgreen
     *                        - yellow
     *                        - lightblue
     *                        - lightmagenta
     *                        - lightcyan
     * @param string $text   The text to print in this color.
     *
     * @return string  The colored text.
     */
    public function color($color, $text)
    {
        if ($this->_format == self::FORMAT_HTML) {
            return '<span style="color:' . $color . '">' . $text . '</span>';
        }

        if ($this->_format != self::FORMAT_XTERM) {
            return $text;
        }

        $colors = $this->_foregroundColors();
        if (isset($colors[$color])) {
            return $colors[$color] . $text . "\x1b[39m";
        }

        return $text;
    }

    /**
     * Returns a colored version of $text, with the method name specifying the
     * color:
     *
     * <code>
     * echo $cliColor->lightred("Foo");
     * </code>
     *
     * @param string $text  The text to print in color.
     *
     * @return string  The colored text.
     */
    public function __call($color, $args)
    {
        return $this->color($color, $args[0]);
    }

    /**
     * Returns a version of $text with a colored background.
     *
     * @param string $color  The background color to use.
     * @param string $text   The text to print on this background.
     *
     * @return string  The text with background.
     */
    public function background($color, $text)
    {
        if ($this->_format == self::FORMAT_HTML) {
            return '<span style="background-color:' . $color . '">'
                . $text . '</span>';
        }

        if ($this->_format != self::FORMAT_XTERM) {
            return $text;
        }

        $colors = $this->_backgroundColors();
        if (isset($colors[$color])) {
            return $colors[$color] . $text . "\x1b[49m";
        }

        return $text;
    }

    /**
     * Removes all color formatting from a text.
     *
     * @param string $text  A colored text.
     *
     * @return string  The text with all coloring markup removed.
     */
    public function remove($text)
    {
        if ($this->_format == self::FORMAT_HTML) {
            $text = str_replace('</span>', '', $text);
            foreach (array_keys(array_merge($this->_foregroundColors(), $this->_backgroundColors())) as $color) {
                $text = str_replace(
                    array(
                        '<span style="color:' . $color . '">',
                        '<span style="background-color:' . $color . '">',
                    ),
                    '',
                    $text
                );
            }
            return $text;
        }

        return str_replace(
            array_merge(
                array_values($this->_foregroundColors()),
                array_values($this->_backgroundColors())
            ),
            '',
            $text
        );
    }

    /**
     * Returns the xterm-compatible foreground color sequences.
     *
     * @return array  Color sequences.
     */
    protected function _foregroundColors()
    {
        return array(
            'bold'         => "\x1b[1m",
            'normal'       => "\x1b[39m",
            'black'        => "\x1b[30m",
            'red'          => "\x1b[31m",
            'green'        => "\x1b[32m",
            'brown'        => "\x1b[33m",
            'blue'         => "\x1b[34m",
            'magenta'      => "\x1b[35m",
            'cyan'         => "\x1b[36m",
            'lightgray'    => "\x1b[37m",
            'darkgray'     => "\x1b[1m\x1b[30m",
            'lightred'     => "\x1b[1m\x1b[31m",
            'lightgreen'   => "\x1b[1m\x1b[32m",
            'yellow'       => "\x1b[1m\x1b[33m",
            'lightblue'    => "\x1b[1m\x1b[34m",
            'lightmagenta' => "\x1b[1m\x1b[35m",
            'lightcyan'    => "\x1b[1m\x1b[36m",
            'white'        => "\x1b[1m\x1b[37m",
        );
    }

    /**
     * Returns the xterm-compatible background color sequences.
     *
     * @return array  Color sequences.
     */
    protected function _backgroundColors()
    {
        return array(
            'normal'    => "\x1b[49m",
            'black'     => "\x1b[40m",
            'red'       => "\x1b[41m",
            'green'     => "\x1b[42m",
            'brown'     => "\x1b[43m",
            'blue'      => "\x1b[44m",
            'magenta'   => "\x1b[45m",
            'cyan'      => "\x1b[46m",
            'lightgray' => "\x1b[47m",
        );
    }
}
