<?php

$block_name = _("Last news");

/**
 * $Id: last.php 890 2008-09-23 09:58:23Z duck $
 *
 * @package Horde_Block
*/

class Horde_Block_News_last extends Horde_Block {

    var $_app = 'news';

    function _title()
    {
        return Horde::link(Horde::applicationUrl('browse.php'), _("Last news"), 'header') . _("Last news") . '</a>';
    }

    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';

        return array('limit'    => array('type' => 'int',
                                         'name' => _("How many news to display?"),
                                         'default' => 10),
                     'category' => array('type' => 'enum',
                                         'name' => _("Skip category"),
                                         'values' => $GLOBALS['news_cat']->getEnum()));
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $params = array(News::CONFIRMED, $GLOBALS['registry']->preferredLang());
        $query = 'SELECT n.id, n.publish, n.comments, n.picture, n.category1, nl.title, nl.abbreviation ' .
                 'FROM ' . $GLOBALS['news']->prefix . ' AS n, ' . $GLOBALS['news']->prefix . '_body AS nl WHERE ' .
                 'n.status = ? AND n.publish <= NOW() ' .
                 'AND nl.lang = ? AND n.id = nl.id ';

        if (!empty($this->_params['category'])) {
            $query .= ' AND n.category1 <> ? ';
            $params[] = (int)$this->_params['category'];
        }

        $query .= 'ORDER BY n.publish DESC';
        $res = $GLOBALS['news']->db->queryLimit($query, 0, $this->_params['limit'], $params);
        if ($res instanceof PEAR_Error) {
            return $res->getDebugInfo();
        }
        $rows = array();
        while ($res->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            $rows[$row['id']] = $row;
        }
        $view = new News_View();
        $view->news = $rows;
        $view->moreurl = Horde::applicationUrl('browse.php');

        return $view->render('/block/news.php');
    }
}
