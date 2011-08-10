<?php
/**
 * Renders a table of contents for a Wiki page.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */

/**
 * Renders a table of contents for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Toc extends Text_Wiki_Render
{
    /**
    * 
    * Renders a token into text matching the requested format.
    * 
    * @access public
    * 
    * @param array $options The "options" portion of the token (second
    * element).
    * 
    * @return string The text rendered from the token options.
    * 
    */
    
    function token($options)
    {
        if($options['type'] == 'list_start') {
            return ".. contents:: Contents\n.. section-numbering::\n\n";
        }
        if($options['type'] == 'item_start') {
            $this->wiki->registerRenderCallback(array($this, 'purge'));
        }
        if($options['type'] == 'item_end') {
            $this->wiki->popRenderCallback();
        }
        return '';
    }

    public function purge($block)
    {
        return '';
    }
}
