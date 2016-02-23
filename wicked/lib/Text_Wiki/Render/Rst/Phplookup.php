<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category Horde
 * @package  Wicked
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Renders links to the PHP manual.
 *
 * @category Horde
 * @package  Wicked
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Phplookup extends Text_Wiki_Render
{
    /**
     * A collector for link sections below a paragraph.
     *
     * @var array
     */
    public static $paragraph_links = array();

    /**
     * Renders a token into text matching the requested format.
     *
     * @param array $options  The "options" portion of the token (second
     *                        element).
     *
     * @return string The text rendered from the token options.
     */
    public function token($options)
    {
        $text = trim($options['text']);

        // take off the final parens for functions
        if (substr($text, -2) == '()') {
            $q = substr($text, 0, -2);
        } else {
            $q = $text;
        }

        $q = $this->urlEncode($q);

        // finish and return
        self::$paragraph_links[] = '.. _`' . $text . '`: http://php.net/' . $q;
        return '`' . $text . '`_';
    }
}
