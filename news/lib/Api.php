<?php
/**
 * News external API.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package News
 */
class News_Api extends Horde_Registry_Api
{
    /**
     * Categories/Permissions
     */
    public function perms()
    {
        static $perms = array();
        if (!empty($perms)) {
            return $perms;
        }

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

    /**
     * Callback for comment API
     *
     * @param int $id                Internal data identifier
     * @param string $type      Type of data to retreive (title, owner...)
     * @param array $params Additional parameters
     */
    public function commentCallback($id, $type = 'title', $params = null)
    {
        static $info;

        if (!empty($info[$id][$type])) {
            return $info[$id][$type];
        }

        require_once dirname(__FILE__) . '/base.php';

        $news = $GLOBALS['news']->get($id);
        if ($news instanceof PEAR_Error) {
            return $news;
        }

        switch ($type) {

        case 'owner':
            return $news['user'];

        case 'link':
            return News::getUrlFor('news', $id, true, -1);

        case 'messages':
            $GLOBALS['news']->updateComments($id, $params);

            if ($GLOBALS['registry']->hasMethod('logActivity', 'folks')) {
                $link = '<a href="' . News::getUrlFor('news', $id) . '">' . $news['title'] . '</a>';
                $message = sprintf(_("Has commented news \"%s\""), $link);
                $GLOBALS['registry']->callByPackage('folks', 'logActivity', array($message, 'news'));
            }

            return true;

        default:
            $info[$id][$type] = $news['title'];
            return $news['title'];
        }
    }

    /**
     * Returns if applications allows comments
     */
    public function hasComments()
    {
        return $GLOBALS['conf']['comments']['allow'];
    }

    /**
     * List news
     *
     * @param array $criteria  Array of news attributes match
     * @param intiger $from    Start fetching from news
     * @param intiger $count  The number of news to fetch
     * @param intiger $perms News permission access type
     *
     * @return array | PEAR_Error  True on success, PEAR_Error on failure.
     */
    public function listNews($criteria = array(), $from = 0, $count = 0, $perms = PERMS_READ)
    {
        require_once dirname(__FILE__) . '/base.php';

        return $GLOBALS['news']->listNews($criteria, $from, $count, $perms);
    }

    /**
     * Count news
     *
     * @param array $criteria Array of news attributes match
     * @param integer $perms Permisson level
     *
     * @return integer | PEAR_Error  True on success, PEAR_Error on failure.
     */
    public function countNews($criteria = array(), $perms = PERMS_READ)
    {
        require_once dirname(__FILE__) . '/base.php';

        return $GLOBALS['news']->countNews($criteria, $perms);
    }

}
