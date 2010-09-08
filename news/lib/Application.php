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
        $perms['tree']['news']['admin'] = true;
        $perms['title']['news:admin'] = _("Admin");

        $perms['tree']['news']['editors'] = true;
        $perms['title']['news:editors'] = _("Editors");

        require_once dirname(__FILE__) . '/base.php';
        $tree = $GLOBALS['news_cat']->getEnum();

        $perms['title']['news:categories'] = _("Categories");
        foreach ($tree as $cat_id => $cat_name) {
            $perms['tree']['news']['categories'][$cat_id] = false;
            $perms['title']['news:categories:' . $cat_id] = $cat_name;
        }

        return $perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return News::getMenu();
    }

}
