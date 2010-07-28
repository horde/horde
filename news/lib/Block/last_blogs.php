<?php

$block_name = _("Last news blogged");

/**
 * @package Horde_Block
*/

class Horde_Block_News_last_blogs extends Horde_Block {

    var $_app = 'news';

    function _title()
    {
        return _("Last news blog");
    }

    function _params()
    {
        return array('limit' => array('type' => 'int',
                                      'name' => _("How many news to display?"),
                                      'default' => 10));
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $query = 'SELECT n.id, n.publish, n.comments, n.picture, n.category1, nl.title, nl.abbreviation ' .
                 'FROM ' . $GLOBALS['news']->prefix . ' AS n, ' . $GLOBALS['news']->prefix . '_body AS nl WHERE ' .
                 'n.status = ? AND n.publish <= NOW() AND n.trackbacks > ? ' .
                 'AND nl.lang = ? AND n.id = nl.id ' .
                 'ORDER BY n.publish DESC';

        $params = array(News::CONFIRMED, 0, $GLOBALS['registry']->preferredLang());
        $res = $GLOBALS['news']->db->limitQuery($query, 0, $this->_params['limit'], $params);
        if ($res instanceof PEAR_Error) {
            return $res->getDebugInfo();
        }
        while ($res->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            $rows[$row['id']] = $row;
        }
        $view = new News_View();
        $view->news = $rows;

        return $view->render('/block/news.php');
    }
}
