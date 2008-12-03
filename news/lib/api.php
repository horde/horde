<?php
/**
 * News api
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: api.php 730 2008-08-10 09:52:55Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray'
);

$_services['commentCallback'] = array(
    'args' => array('id' => 'string'),
    'type' => 'string'
);

$_services['hasComments'] = array(
    'args' => array(),
    'type' => 'boolean'
);

$_services['listNews'] = array(
    'args' => array('perms' => 'string', 'criteria' => 'array', 'from' => 'int', 'count' => 'int'),
    'type' => 'array'
);

$_services['countNews'] = array(
    'args' => array('perms' => 'string', 'criteria' => 'array'),
    'type' => 'int'
);

/**
 * Categories/Permissions
 */
function _news_perms()
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
 * Callback for comment API
 *
 * @param int $id                Internal data identifier
 * @param string $type      Type of data to retreive (title, owner...)
 * @param array $params Additional parameters
 */
function _news_commentCallback($id, $type = 'title', $params = null)
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
        return Util::addParameter(Horde::applicationUrl('news.php', true, -1), 'id', $id);

    case 'messages':
        $GLOBALS['news']->updateComments($id, $params);
        return true;

    default:
        $info[$id][$type] = $news['title'];
        return $news['title'];
    }
}

/**
 * Returns if applications allows comments
 */
function _news_hasComments()
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
function _news_listNews($criteria = array(), $from = 0, $count = 0, $perms = PERMS_READ)
{
    require_once dirname(__FILE__) . '/base.php';

    return $GLOBALS['news']->listNews($criteria, $perms);
}

/**
 * Count news
 *
 * @param array $criteria Array of news attributes match
 * @param integer $perms Permisson level
 *
 * @return integer | PEAR_Error  True on success, PEAR_Error on failure.
 */
function _news_countNews($criteria = array(), $perms = PERMS_READ)
{
    require_once dirname(__FILE__) . '/base.php';

    return $GLOBALS['news']->countNews($criteria, $perms);
}

