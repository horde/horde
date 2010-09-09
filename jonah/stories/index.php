<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah');
$news = Jonah_News::factory();

/* Redirect to the news index if no channel_id is specified. */
$channel_id = Horde_Util::getFormData('channel_id');
if (empty($channel_id)) {
    $notification->push(_("No channel requested."), 'horde.error');
    Horde::url('channels/index.php', true)->redirect();
}

$channel = $news->getChannel($channel_id);
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::EDIT, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* Check if a forced refresh is being called for an external channel. */
$refresh = Horde_Util::getFormData('refresh');

/* Check if a URL has been passed. */
$url = Horde_Util::getFormData('url');

$stories = $news->getStories($channel_id, null, 0, !empty($refresh), null, true);
if (is_a($stories, 'PEAR_Error')) {
    $notification->push(sprintf(_("Invalid channel requested. %s"), $stories->getMessage()), 'horde.error');
    Horde::url('channels/index.php', true)->redirect();
}

/* Do some state tests. */
if (empty($stories)) {
    $notification->push(_("No available stories."), 'horde.warning');
}
if (!empty($refresh)) {
    $notification->push(_("Channel refreshed."), 'horde.success');
}
if (!empty($url)) {
    header('Location: ' . $url);
    exit;
}

/* Get channel details, for title, etc. */
$channel = $news->getChannel($channel_id);

$allow_delete = Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::DELETE, $channel_id);

/* Build story specific fields. */
foreach ($stories as $key => $story) {
    /* story_published is the publication/release date, story_updated
     * is the last change date. */
    if (!empty($stories[$key]['story_published'])) {
        $stories[$key]['story_published_date'] = strftime($prefs->getValue('date_format') . ', ' . ($prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M%p'), $stories[$key]['story_published']);
    } else {
        $stories[$key]['story_published_date'] = '';
    }

    /* Default to no links. */
    $stories[$key]['pdf_link'] = '';
    $stories[$key]['edit_link'] = '';
    $stories[$key]['delete_link'] = '';

    /* These links only if internal channel. */
    if ($channel['channel_type'] == Jonah::INTERNAL_CHANNEL ||
        $channel['channel_type'] == Jonah::COMPOSITE_CHANNEL) {
        $stories[$key]['view_link'] = Horde::link(Horde::url($story['story_link']), $story['story_desc']) . htmlspecialchars($story['story_title']) . '</a>';

        /* PDF link. */
        $url = Horde::url('stories/pdf.php');
        $url = Horde_Util::addParameter($url, array('story_id' => $story['story_id'], 'channel_id' => $channel_id));
        $stories[$key]['pdf_link'] = Horde::link($url, _("PDF version")) . Horde::img('mime/pdf.png') . '</a>';

        /* Edit story link. */
        $url = Horde::url('stories/edit.php');
        $url = Horde_Util::addParameter($url, array('story_id' => $story['story_id'], 'channel_id' => $channel_id));
        $stories[$key]['edit_link'] = Horde::link($url, _("Edit story")) . Horde::img('edit.png') . '</a>';

        /* Delete story link. */
        if ($allow_delete) {
            $url = Horde::url('stories/delete.php');
            $url = Horde_Util::addParameter($url, array('story_id' => $story['story_id'], 'channel_id' => $channel_id));
            $stories[$key]['delete_link'] = Horde::link($url, _("Delete story")) . Horde::img('delete.png') . '</a>';
        }

        /* Comment counter. */
        if ($conf['comments']['allow'] &&
            $registry->hasMethod('forums/numMessages')) {
            $comments = $registry->call('forums/numMessages', array($stories[$key]['story_id'], 'jonah'));
            if (!is_a($comments, 'PEAR_Error')) {
                $stories[$key]['comments'] = $comments;
            }
        }
    } else {
        if (!empty($story['story_body'])) {
            $stories[$key]['view_link'] = Horde::link(Horde::url($story['story_link']), $story['story_desc'], '', '_blank') . htmlspecialchars($story['story_title']) . '</a>';
        } else {
            $stories[$key]['view_link'] = Horde::link(Horde::externalUrl($story['story_url']), $story['story_desc'], '', '_blank') . htmlspecialchars($story['story_title']) . '</a>';
        }
    }
}

$template = new Horde_Template();
$template->setOption('gettext', true);
$template->set('header', htmlspecialchars($channel['channel_name']));
$template->set('refresh', Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), array('refresh' => 1)), _("Refresh Channel")) . Horde::img('reload.png') . '</a>');
$template->set('listheaders', array(_("Story"), _("Date")));
$template->set('stories', $stories, true);
$template->set('read', $channel['channel_type'] == Jonah::INTERNAL_CHANNEL || $channel['channel_type'] == Jonah::COMPOSITE_CHANNEL, true);
$template->set('comments', $conf['comments']['allow'] && $registry->hasMethod('forums/numMessages') && $channel['channel_type'] == Jonah::INTERNAL_CHANNEL, true);
$template->set('menu', Horde::menu());

// Buffer the notifications and send to the template
Horde::startBuffer();
$GLOBALS['notification']->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

$title = $channel['channel_name'];
require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/stories/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
