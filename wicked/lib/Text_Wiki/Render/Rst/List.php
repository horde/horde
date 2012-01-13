<?php
/**
 * Renders a list for a Wiki page.
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
 * Renders a list for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_List
{
    /**
     * Render the list.
     *
     * @param array $options The rendering options.
     *
     * @return string The output string.
     */
    public function token($options)
    {
        // make nice variables (type, level, count)
        extract($options);

        switch ($type) {
        case 'bullet_list_start':
        case 'bullet_list_end':
        case 'number_list_start':
        case 'number_list_end':
            return '';
        case 'bullet_item_start':
            return str_repeat(' ', ($level - 1) * 2) . '* ';
        case 'number_item_start':
            return str_repeat(' ', ($level - 1) * 2) . ($count + 1) . '. ';
        case 'bullet_item_end':
        case 'number_item_end':
            return "\n";
        default:
            // ignore item endings and all other types.
            // item endings are taken care of by the other types
            // depending on their place in the list.
            return '';
            break;
        }
    }
}
