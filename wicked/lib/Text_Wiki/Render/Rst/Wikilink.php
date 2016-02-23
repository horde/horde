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
 * Renders a link to another wiki page for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Wikilink extends Text_Wiki_Render
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
        // make nice variable names (page, anchor, text, type)
        extract($options);

        // is there a "page existence" callback?
        // we need to access it directly instead of through
        // getConf() because we'll need a reference (for
        // object instance method callbacks).
        if (isset($this->conf['exists_callback'])) {
            $callback = $this->conf['exists_callback'];
        } else {
            $callback = false;
        }

        if ($callback) {
            // use the callback function
            $exists = call_user_func($callback, $page);
        } else {
            // no callback, go to the naive page array.
            $list = $this->getConf('pages');
            if (is_array($list)) {
                // yes, check against the page list
                $exists = in_array($page, $list);
            } else {
                // no, assume it exists
                $exists = true;
            }
        }

        // Does the page exist?
        if ($exists) {
            $href = sprintf(
                preg_replace('/%(?!s)/', '%%', $this->getConf('view_url')),
                $page
            )
                . $anchor;
            if (!strlen($text)) {
                $text = $page;
            }
            self::$paragraph_links[] = '.. _`' . $text . '`: ' . $href;
            return '`' . $text . '`_';
        }

        $new_url = $this->getConf('new_url');
        if (!$new_url) {
            return strlen($text) ? $text : $page;
        }

        $href = sprintf(
            preg_replace('/%(?!s)/', '%%', $new_url),
            $page
        );

        // what kind of linking are we doing?
        $new = $this->getConf('new_text');
        if ($new) {
            if ($this->getConf('new_text_pos') == 'before') {
                // use the new_text BEFORE the page name
                $start = $new;
                $end = '';
            } else {
                // default, use the new_text link AFTER the page name
                $start = '';
                $end = $new;
            }
        }
        if (!strlen($text)) {
            $start .= $page;
        }
        if (isset($type)) {
            switch ($type) {
            case 'start':
                $output = $start;
                break;
            case 'end':
                $output = $end;
                break;
            }
        } else {
            $output = $start . $text . $end;
        }

        self::$paragraph_links[] = '.. _`' . $output . '`: ' . $href;
        return '`' . $output . '`_';
    }
}
