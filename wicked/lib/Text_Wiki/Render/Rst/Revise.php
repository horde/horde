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
 * Renders revised text for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Jan Schneider <jan@horde.org>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Revise extends Text_Wiki_Render
{

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
        switch ($options['type']) {
        case 'del_start':
        case 'del_end':
            return '--';
        case 'ins_start':
        case 'ins_end':
            return '++';
        }
    }
}
