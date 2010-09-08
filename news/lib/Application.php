<?php
/**
 * News application API.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package News
 */
class News_Application extends Horde_Registry_Application
{
    public $version = 'H4 (0.1-git)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array(
            'admin' => array(
                'title' => _("Admin")
            ),
            'categories' => array(
                'title' => _("Categories")
            ),
            'editors' => array(
                'title' => _("Editors")
            )
        );

        require_once dirname(__FILE__) . '/base.php';
        $tree = $GLOBALS['news_cat']->getEnum();

        foreach ($tree as $cat_id => $cat_name) {
            $perms['categories:' . $cat_id] = array(
                'title' => $cat_name
            );
        }

        return $perms;
    }

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        return News::getMenu();
    }

}
