<?php

$block_name = _("Last most read news");

/**
 * $Id: most_read.php 890 2008-09-23 09:58:23Z duck $
 *
 * @package Horde_Block
*/

class Horde_Block_News_most_read extends Horde_Block {

    var $_app = 'news';

    function _title()
    {
        return _("Last most read news");
    }

    function _params()
    {
        return array('limit' => array('type' => 'int',
                                      'name' => _("How many news to display?"),
                                      'default' => 10),
                     'days' => array('type' => 'int',
                                      'name' => _("How many days back to check?"),
                                      'default' => 30));
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $query = 'SELECT n.id, n.publish, n.comments, n.picture, nl.title, nl.abbreviation ' .
                 'FROM ' . $GLOBALS['news']->prefix . ' AS n, ' . $GLOBALS['news']->prefix . '_body AS nl WHERE ' .
                 'n.status = ? AND n.publish <= NOW() AND n.publish > ?' .
                 'AND nl.lang = ? AND n.id = nl.id ' .
                 'ORDER BY n.view_count DESC';

        $younger = $_SERVER['REQUEST_TIME'] - $this->_params['days'] * 86400;
        $params = array(News::CONFIRMED, date('Y-m-d', $younger), $GLOBALS['registry']->preferredLang());
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

        return $view->render('/block/titles.php');
    }
}
