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

@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';

if (!Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde_Auth::authenticateFailure();
}

$have_news = Jonah_News::getAvailableTypes();
if (empty($have_news)) {
    $notification->push(_("News is not enabled."), 'horde.warning');
    $url = Horde::applicationUrl('index.php', true);
    header('Location: ' . $url);
    exit;
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
        $url = Horde::applicationUrl('channels/edit.php');
        $url = Horde_Util::addParameter($url, 'channel_id', $channel['channel_id']);
        $channels[$key]['edit_link'] = Horde::link($url, _("Edit channel"), '', '', '', _("Edit channel")) . Horde::img('edit.png', _("Edit channel"), '', $registry->getImageDir('horde')) . '</a>';

        /* Delete channel link. */
        $url = Horde::applicationUrl('channels/delete.php');
        $url = Horde_Util::addParameter($url, 'channel_id', $channel['channel_id']);
        $channels[$key]['delete_link'] = Horde::link($url, _("Delete channel"), '', '', '', _("Delete channel")) . Horde::img('delete.png', _("Delete channel"), null, $registry->getImageDir('horde')) . '</a>';

        /* View stories link. */
        $url = Horde::applicationUrl('stories/index.php');
        $url = Horde_Util::addParameter($url, 'channel_id', $channel['channel_id']);
        $channels[$key]['stories_url'] = $url;

        /* Channel type specific links. */
        $channels[$key]['addstory_link'] = '';
        $channels[$key]['refresh_link'] = '';

        switch ($channel['channel_type']) {
        case JONAH_INTERNAL_CHANNEL:
            /* Add story link. */
            $url = Horde::applicationUrl('stories/edit.php');
            $url = Horde_Util::addParameter($url, 'channel_id', $channel['channel_id']);
            $channels[$key]['addstory_link'] = Horde::link($url, _("Add story"), '', '', '', _("Add story")) . Horde::img('new.png', _("Add story")) . '</a>';
            break;

        case JONAH_EXTERNAL_CHANNEL:
        case JONAH_AGGREGATED_CHANNEL:
            /* Refresh cache link. */
            $url = Horde::applicationUrl('stories/index.php');
            $url = Horde_Util::addParameter($url, array('channel_id' => $channel['channel_id'], 'refresh' => '1', 'url' => Horde::selfUrl()));
            $channels[$key]['refresh_link'] = Horde::link($url, _("Refresh channel"), '', '', '', _("Refresh channel")) . Horde::img('reload.png', _("Refresh channel"), '', $registry->getImageDir('horde')) . '</a>';
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
$template->set('search_img', Horde::img('search.png', _("Search"), '', $registry->getImageDir('horde')));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

$title = _("Feeds");
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('tables.js', 'horde', true);
Horde::addScriptFile('QuickFinder.js', 'horde', true);
require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/channels/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
