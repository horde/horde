<?php

$block_name = _("Feeds");

/**
 * This class extends Horde_Block:: to provide a list of deliverable internal
 * channels.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Roel Gloudemans <roel@gloudemans.info>
 * @package Horde_Block
 */
class Horde_Block_Jonah_delivery extends Horde_Block
{
    var $_app = 'jonah';

    function _title()
    {
        return _("Feeds");
    }

    function _content()
    {
        try {
            $channels = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannels();
        } catch (Jonah_Exception $e) {
            $channels = array();
        }

        $html = '';

        foreach ($channels as $key => $channel) {
            /* Link for HTML delivery. */
            $url = Horde::url('delivery/html.php')->add('channel_id', $channel['channel_id']);
            $label = sprintf(_("\"%s\" stories in HTML"), $channel['channel_name']);
            $html .= '<tr><td width="140">' .
                Horde::img('story_marker.png') . ' ' .
                $url->link(array('title' => $label)) .
                htmlspecialchars($channel['channel_name']) . '</a></td>';

            $html .= '<td>' . ($channel['channel_updated'] ? date('M d, Y H:i', (int)$channel['channel_updated']) : '-') . '</td>';

            /* Link for feed delivery. */
            $url = Horde::url('delivery/rss.php', true, -1)->add('channel_id', $channel['channel_id']);
            $label = sprintf(_("RSS Feed of \"%s\""), $channel['channel_name']);
            $html .= '<td align="right" class="nowrap">' .
                     $url->link(array('title' => $label)) .
                     Horde::img('feed.png') . '</a> ';
        }

        if ($html) {
            return '<table cellspacing="0" width="100%" class="linedRow striped">' . $html . '</table>';
        } else {
            return '<p><em>' . _("No feeds are available.") . '</em></p>';
        }
    }

}
