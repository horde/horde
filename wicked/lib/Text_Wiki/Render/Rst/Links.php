<?php
/**
 * Renders collected links for a Wiki page.
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
 * Renders collected links for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
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
