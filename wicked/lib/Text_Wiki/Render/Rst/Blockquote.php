<?php
/**
 * Renders quoted text for a Wiki page.
 *
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
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
 * Renders quoted text for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Blockquote extends Text_Wiki_Render
{
    /**
     * Renders a token into text matching the requested format.
     * 
     * @param array $options The "options" portion of the token (second
     * element).
     * 
     * @return string The text rendered from the token options.
     */
    public function token($options)
    {
        // starting
        if ($options['type'] == 'start') {
            $this->wiki->registerRenderCallback(array($this, 'renderInsideText'));
            $this->_level = $options['level'];
            return '';
        }

        // ending
        if ($options['type'] == 'end') {
            $this->wiki->popRenderCallback();
            return "\n";
        }
    }

    public function renderInsideText($text)
    {
        return preg_replace('/(^|\n)(>*) */',
                            '\1' . str_repeat(' ', $this->_level * 2). '\2',
                            trim($text))
            . "\n";
    }
}
