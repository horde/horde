<?php
/**
 * Renders collected links for a Wiki page.
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
 * Renders collected links for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Links
{
    static public function append()
    {
        $result = '';
        $links = array_merge(
            Text_Wiki_Render_Rst_Url::$paragraph_links,
            Text_Wiki_Render_Rst_Freelink::$paragraph_links
        );
        if (!empty($links)) {
            $result .= "\n";
            foreach ($links as $link) {
                $result .= "\n" . $link ;
            }
            Text_Wiki_Render_Rst_Url::$paragraph_links = array();
            Text_Wiki_Render_Rst_Freelink::$paragraph_links = array();
        }
        return $result;
    }
}
