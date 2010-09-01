<?php

if ($GLOBALS['registry']->hasInterface('news')) {
    $block_name = _("Press overview");
}

/**
 * $Id: jonah.php 890 2008-09-23 09:58:23Z duck $
 *
 * @package Horde_Block
 */
class Horde_Block_News_jonah extends Horde_Block {

    var $_app = 'news';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        $url = $GLOBALS['registry']->get('webroot', 'jonah') . '/';
        return Horde::link($url, _("Press overview")) . _("Press overview") . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        $html = '<div id="feed_content" name="feed_content">';
        $html .= _("Select a feed.");
        $html .= '</div>';

        require_once dirname(__FILE__) . '/../base.php';

        $sql = 'SELECT channel_id, channel_name '
             . ' FROM jonah_channels WHERE channel_type = 1 ORDER BY channel_name';
        $chanels = $GLOBALS['news']->db->getAll($sql);

        $html .= '<form action="' . Horde::url('feed.php') . '" id="feed_select" name="feed_select">'
              . '<select id="feed_id" name="feed_id" onchange="getFeed()">'
              . '<option>- - - - - - - </option>';
        foreach ($chanels as $chanel) {
            $html .= '<option value="'  . $chanel[0] . '">'  . $chanel[1] . '</option>';
        }
        $html .= '</select></form>';

        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('redbox.js', 'horde');
        Horde::addScriptFile('feed.js', 'news');

        return $html;
    }
}
