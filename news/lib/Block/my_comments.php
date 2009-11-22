<?php

$block_name = _("Last comments on my news");

/**
 * Horde_Block_news_my_comments:: Implementation of the Horde_Block API to
 * display last comments on users videos.
 *
 * @package Horde_Block
 */
class Horde_Block_news_my_comments extends Horde_Block {

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
        return ("Last comments on my news");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        if (!Horde_Auth::isAuthenticated()) {
            return '';
        }

        $GLOBALS['cache'] = Horde_Cache::singleton($GLOBALS['conf']['cache']['driver'],
                                           Horde::getDriverConfig('cache', $GLOBALS['conf']['cache']['driver']));

        $cache_key = 'news_myscommetns_' . $this->_params['limit'];
        $threads = $GLOBALS['cache']->get($cache_key, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($threads) {
            return $threads;
        }

        Horde::addScriptFile('tables.js', 'horde');
        $html = '<table class="sortable striped" id="my_comment_list" style="width: 100%">'
              . '<thead><tr><th>' . _("Title") . '</th>'
              . '<th>' . _("User") . '</th></tr></thead>';

        try {
            $threads = $GLOBALS['registry']->call('forums/getThreadsByForumOwner',
                                        array(Horde_Auth::getAuth(), 'message_timestamp', 1, false,
                                                'news', 0, $this->_params['limit']));
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }

        foreach ($threads as $message) {
            $html .= '<tr><td>'
                  . '<a href="' . News::getUrlFor('news', $message['forum_name']) . '" title="' . $message['message_date'] . '">'
                  . $message['message_subject'] . '</a> '
                  . '</td><td>'
                  . $message['message_author'] . '</td></tr>';
        }
        $html .= '</table>';

        $GLOBALS['cache']->set($cache_key, $html);
        return $html;
    }
}
