<?php

$block_name = _("Last news in category");

/**
 * $Id: category.php 890 2008-09-23 09:58:23Z duck $
 *
 * @package Horde_Block
*/

class Horde_Block_News_category extends Horde_Block {

    var $_app = 'news';

    function _title()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $url = Horde_Util::addParameter(Horde::applicationUrl('browse.php'), 'category', $this->_params['category']);
        $name = $GLOBALS['news_cat']->getName($this->_params['category']);
        $html = Horde::link($url, sprintf(_("Last news in %s"), $name), 'header');
        $html .= sprintf(_("Last news in %s"), $name) . '</a>';

        return $html;
    }

    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';

        return array('limit'    => array('type' => 'int',
                                         'name' => _("How many news to display?"),
                                         'default' => 10),
                     'category' => array('type' => 'enum',
                                         'name' => _("Category"),
                                         'values' => $GLOBALS['news_cat']->getEnum()));
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $query = 'SELECT n.id, n.publish, n.comments, n.picture, n.category1, nl.title, nl.abbreviation ' .
                 'FROM ' . $GLOBALS['news']->prefix . ' AS n, ' . $GLOBALS['news']->prefix . '_body AS nl WHERE ' .
                 'n.status = ? AND n.publish <= NOW() ' .
                 'AND (n.category1 = ? OR n.category2 = ?) ' .
                 'AND nl.lang = ? AND n.id = nl.id ' .
                 'ORDER BY n.publish DESC ' .
                 'LIMIT 0, ' . $this->_params['limit'];

        $params = array(News::CONFIRMED, $this->_params['category'], $this->_params['category'], $GLOBALS['registry']->preferredLang());
        $rows = $GLOBALS['news']->db->getAll($query, $params, DB_FETCHMODE_ASSOC);
        if ($rows instanceof PEAR_Error) {
            return $rows->getDebugInfo();
        }

        $view = new News_View();
        $view->news = $rows;
        $view->moreurl = Horde_Util::addParameter(Horde::applicationUrl('browse.php'), 'category', $this->_params['category']);

        return $view->render('/block/news.php');
    }
}
