<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * $Horde: jonah/channels/index.php,v 1.49 2009/11/24 04:15:37 chuck Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah');

if (!Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    $registry->authenticateFailure();
}

$have_news = Jonah_News::getAvailableTypes();
if (empty($have_news)) {
    $notification->push(_("News is not enabled."), 'horde.warning');
    Horde::url('index.php', true)->redirect();
}

$news = Jonah_News::factory();

$channels = $news->getChannels(array_keys($have_news));
if (is_a($channels, 'PEAR_Error')) {
    $notification->push(sprintf(_("An error occurred fetching channels: %s"), $channels->getMessage()), 'horde.error');
    $channels = false;
} elseif ($channels) {
    $channels = Jonah::checkPermissions('channels', Horde_Perms::SHOW, $channels);
    /* Build channel specific fields. */
    foreach ($channels as $key => $channel) {
        /* Edit channel link. */
        $url = Horde::url('channels/edit.php');
        $url = Horde_Util::addParameter($url, 'channel_id', $channel['channel_id']);
        $channels[$key]['edit_link'] = Horde::link($url, _("Edit channel"), '', '', '', _("Edit channel")) . Horde::img('edit.png') . '</a>';

        /* Delete channel link. */
        $url = Horde::url('channels/delete.php');
        $url = Horde_Util::addParameter($url, 'channel_id', $channel['channel_id']);
        $channels[$key]['delete_link'] = Horde::link($url, _("Delete channel"), '', '', '', _("Delete channel")) . Horde::img('delete.png') . '</a>';

        /* View stories link. */
        $url = Horde::url('stories/index.php');
        $url = Horde_Util::addParameter($url, 'channel_id', $channel['channel_id']);
        $channels[$key]['stories_url'] = $url;

        /* Channel type specific links. */
        $channels[$key]['addstory_link'] = '';
        $channels[$key]['refresh_link'] = '';

        switch ($channel['channel_type']) {
        case Jonah::INTERNAL_CHANNEL:
            /* Add story link. */
            $url = Horde::url('stories/edit.php');
            $url = Horde_Util::addParameter($url, 'channel_id', $channel['channel_id']);
            $channels[$key]['addstory_link'] = Horde::link($url, _("Add story"), '', '', '', _("Add story")) . Horde::img('new.png') . '</a>';
            break;

        case Jonah::EXTERNAL_CHANNEL:
        case Jonah::AGGREGATED_CHANNEL:
            /* Refresh cache link. */
            $url = Horde::url('stories/index.php');
            $url = Horde_Util::addParameter($url, array('channel_id' => $channel['channel_id'], 'refresh' => '1', 'url' => Horde::selfUrl()));
            $channels[$key]['refresh_link'] = Horde::link($url, _("Refresh channel"), '', '', '', _("Refresh channel")) . Horde::img('reload.png') . '</a>';
            break;
        }

        $channels[$key]['channel_type'] = Jonah::getChannelTypeLabel($channel['channel_type']);
        /* TODO: pref setting for date display. */
        $channels[$key]['channel_updated'] = ($channel['channel_updated'] ? date('M d, Y H:i', (int)$channel['channel_updated']) : '-');
    }
}

$template = new Horde_Template();
$template->set('header', _("Manage Feeds"));
$template->set('listheaders', array(array('attrs' => ' class="sortdown"', 'label' => _("Name")),
                                    array('attrs' => '', 'label' => _("Type")),
                                    array('attrs' => '', 'label' => _("Last Update"))));
$template->set('channels', $channels, true);
$template->set('menu', Jonah::getMenu('string'));
$template->set('search_img', Horde::img('search.png'));

// Buffer the notifications and send to the template
Horde::startBuffer();
$GLOBALS['notification']->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

$title = _("Feeds");
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('tables.js', 'horde', true);
Horde::addScriptFile('quickfinder.js', 'horde', true);
require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/channels/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
