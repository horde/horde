<?php

$block_name = _("Last comments");

/**
 * thomas_Minisearch_Block:: Implementation of the Horde_Block API to
 * allows searching of addressbooks from the portal.
 *
 * $Id: last_comments.php 890 2008-09-23 09:58:23Z duck $
 *
 * @package Horde_Block
 */
class Horde_Block_news_last_comments extends Horde_Block {

    var $_app = 'news';

    function _params()
    {
        return array('limit' => array('name' => _("Number of comments to display"),
                                      'type' => 'int',
                                      'default' => 10));
    }

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Last comments");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../News.php';

        $comments = News::getLastComments($this->_params['limit']);
        if ($comments instanceof PEAR_Error) {
            return $comments;
        }

        $html = '';
        foreach ($comments as $message) {
            $html .= '- '
                  . Horde::link($message['read_url']) . $message['message_subject'] . '</a> '
                  . ' (' . $message['message_author'] . ') <br />';
        }
        return $html;
    }
}