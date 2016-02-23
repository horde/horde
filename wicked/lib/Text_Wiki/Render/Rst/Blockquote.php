<?php
/**
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
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
     * A caching stack for blockquote levels.
     *
     * @var array
     */
    protected $_level;

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
            $this->wiki->registerRenderCallback(
                array($this, 'renderInsideText')
            );
            if ($options['level'] == 1) {
                $this->_level = array($options['level']);
            } else {
                $this->_level[] = $options['level'];
            }
            return '';
        }

        // ending
        if ($options['type'] == 'end') {
            return $this->wiki->popRenderCallback() . "\n\n";
        }
    }

    public function renderInsideText($text)
    {
        return preg_replace(
            '/^(?! )/m',
            "\n" . str_repeat(' ', array_pop($this->_level) * 2),
            trim($text)
        );
    }
}
