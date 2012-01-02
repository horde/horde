<?php
/**
 * Renders Wiki page headers to restructured text.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see
 * http://www.horde.org/licenses/gpl
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Renders Wiki page headers to restructured text.
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Heading extends Text_Wiki_Render
{
    /**
     * The start options.
     *
     * @var array
     */
    private $_previous;

    /**
     * Render the header.
     *
     * @param array $options The rendering options.
     *
     * @return string The output string.
     */
    public function token($options)
    {
        // get nice variable names (type, level)
        extract($options);

        if ($type == 'start') {
            $length = strlen($text);
            switch ($level) {
            case '1':
                $overline = '=';
                $underline = '=';
                $length += 2;
                break;
            case '2':
                $overline = '-';
                $underline = '-';
                $length += 2;
                break;
            case '3':
                $overline = null;
                $underline = '=';
                break;
            case '4':
                $overline = null;
                $underline = '*';
                break;
            case '5':
                $overline = null;
                $underline = '-';
                break;
            case '6':
                $overline = null;
                $underline = '`';
                break;
            }
            $output = '';
            if ($overline !== null) {
                $output .= str_repeat($overline, $length) . "\n ";
            }
            $previous = $options;
            $previous['length'] = $length;
            $previous['underline'] = $underline;
            $this->_previous[] = $previous;
            return $output;
        }

        if ($type == 'end') {
            $previous = array_pop($this->_previous);
            if ($level != $previous['level']) {
                return sprintf(
                    'UNEXPECTED HEADER LEVEL: %s [expected %s]',
                    $level,
                    $previous['level']
                );
            }
            return "\n" . str_repeat($previous['underline'], $previous['length']) . "\n\n";
        }
    }
}
