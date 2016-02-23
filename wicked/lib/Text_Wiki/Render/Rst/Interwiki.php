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
 * Renders a link to another wiki for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Interwiki extends Text_Wiki_Render_Xhtml_Interwiki
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
     * @return string  The text rendered from the token options.
     */
    public function token($options)
    {
        $site = $options['site'];
        $page = $options['page'];
        $text = $options['text'];

        if (isset($this->conf['sites'][$site])) {
            $href = $this->conf['sites'][$site];
        } else {
            return $text;
        }

        // old form where page is at end,
        // or new form with %s placeholder for sprintf()?
        if (strpos($href, '%s') === false) {
            // use the old form
            $href = $href . $page;
        } else {
            // use the new form
            $href = sprintf($href, $page);
        }

        self::$paragraph_links[] = '.. _`' . $text . '`: ' . $href;
        return '`' . $text . '`_';
    }
}
