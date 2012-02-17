<?php
/**
 * Renders a URL for a Wiki page.
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
 * Renders a URL for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Url extends Text_Wiki_Render
{
    /**
     * A collector for link sections below a paragraph.
     *
     * @var array
     */
    static public $paragraph_links = array();

    /**
     * Renders a token into text matching the requested format.
     * 
     * @param array $options The "options" portion of the token (second
     *                       element).
     * 
     * @return string The text rendered from the token options.
     */
    public function token($options)
    {
        extract($options);

        if ($type == 'inline') {
            return $text;
        }
        if ($type == 'descr') {
            self::$paragraph_links[] = '.. _`' . $text . '`: ' . $href;
            return '`' . $text . '`_';
        }
    }
}
