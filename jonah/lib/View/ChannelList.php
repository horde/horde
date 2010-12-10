<?php
/**
 * View for displaying Jonah channels.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_View_ChannelList extends Jonah_View_Base
{
    /**
     *
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);
        try {
            $channels = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannels();
        } catch (Exception $e) {
            $notification->push(sprintf(_("An error occurred fetching channels: %s"), $e->getMessage()), 'horde.error');
            $channels = false;
        }
        if ($channels) {
            $channels = Jonah::checkPermissions('channels', Horde_Perms::SHOW, $channels);
            /* Build channel specific fields. */
            foreach ($channels as $key => $channel) {
                /* Edit channel link. */
                $url = Horde::url('channels/edit.php')->add('channel_id', $channel['channel_id']);
                $channels[$key]['edit_link'] = $url->link(array('title' => _("Edit channel"))) . Horde::img('edit.png') . '</a>';

                /* Delete channel link. */
                $url = Horde::url('channels/delete.php')->add('channel_id', $channel['channel_id']);
                $channels[$key]['delete_link'] = $url->link(array('title' => _("Delete channel"))) . Horde::img('delete.png') . '</a>';

                /* View stories link. */
                $channels[$key]['stories_url'] = Horde::url('stories/index.php')->add('channel_id', $channel['channel_id']);

                /* Channel type specific links. */
                $channels[$key]['addstory_link'] = '';
                $channels[$key]['refresh_link'] = '';

                switch ($channel['channel_type']) {
                case Jonah::INTERNAL_CHANNEL:
                    /* Add story link. */
                    $url = Horde::url('stories/edit.php')->add('channel_id', $channel['channel_id']);
                    $channels[$key]['addstory_link'] = $url->link(array('title' => _("Add story"))) . Horde::img('new.png') . '</a>';
                    break;
                }
                $channels[$key]['channel_type'] = Jonah::getChannelTypeLabel($channel['channel_type']);
                $channels[$key]['channel_updated'] = ($channel['channel_updated'] ? strftime($prefs->getValue('date_format'), (int)$channel['channel_updated']) : '-');
            }
        }

        $view = new Horde_View(array('templatePath' => JONAH_TEMPLATES . '/view'));
        $view->addHelper('Tag');
        $view->channels = $channels;
        $view->search_img = Horde::img('search.png');
        $title = _("Feeds");
        Horde::addScriptFile('prototype.js', 'horde', true);
        Horde::addScriptFile('tables.js', 'horde', true);
        Horde::addScriptFile('quickfinder.js', 'horde', true);
        require $registry->get('templates', 'horde') . '/common-header.inc';
        require JONAH_TEMPLATES . '/menu.inc';
        echo $view->render('channellist');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}