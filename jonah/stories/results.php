<?php
/**
 * Display list of articles that match a tag query.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('jonah');
$driver = $GLOBALS['injector']->getInstance('Jonah_Driver');

/* Redirect to the news index if no tag_id is specified. */
$tag_id = Horde_Util::getFormData('tag_id');

/* If a channel_id is passed in, use it - otherwise we assume this is
 * a search for tags for ALL visible internal channels. */
$channel_id = Horde_Util::getFormData('channel_id', null);
if (!is_null($channel_id)) {
    $channel = $driver->getChannel($channel_id);
    if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::SHOW, $channel_id)) {
        $notification->push(_("You are not authorised for this action."), 'horde.warning');
        $registry->authenticationFailure();
    }
    $channel_ids = array($channel_id);
} else {
    $channel_ids = array();
    $channels = $driver->getChannels();
    foreach ($channels as $ch) {
        if (Jonah::checkPermissions(Jonah::typeToPermName($ch['channel_type']), Horde_Perms::SHOW, $ch['channel_id'])) {
            $channel_ids[] = $ch['channel_id'];
        }
    }
}

/* Make sure we actually requested a tag search */
if (empty($tag_id)) {
    $notification->push(_("No tag requested."), 'horde.error');
    Horde::url('channels/index.php', true)->redirect();
}

$tag_name = array_shift($driver->getTagNames(array($tag_id)));

try {
    $stories = $driver->searchTagsById(array($tag_id), 10, 0, $channel_ids);
} catch (Exception $e) {
    $notification->push(sprintf(_("Invalid channel requested. %s"), $e->getMessage()), 'horde.error');
    Horde::url('channels/index.php', true)->redirect();
    exit;
}

/* Do some state tests. */
if (empty($stories)) {
    $notification->push(_("No available stories."), 'horde.warning');
}

foreach ($stories as $key => $story) {
    /* Use the channel_id from the story hash since we might be dealing
    with more than one channel. */
    $channel_id = $story['channel_id'];

    if (!empty($stories[$key]['published'])) {
        $stories[$key]['published_date'] = strftime($prefs->getValue('date_format') . ', ' . ($prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M%p'), $stories[$key]['published']);
    } else {
        $stories[$key]['published_date'] = '';
    }

    /* Default to no links. */
    $stories[$key]['pdf_link'] = '';
    $stories[$key]['edit_link'] = '';
    $stories[$key]['delete_link'] = '';
    $stories[$key]['view_link'] = Horde::url($story['link'])->link(array('title' => $story['desc'])) . htmlspecialchars($story['title']) . '</a>';

    /* PDF link. */
    $url = Horde::url('stories/pdf.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
    $stories[$key]['pdf_link'] = $url->link(array('title' => _("PDF version"))) . Horde::img('mime/pdf.png') . '</a>';

    /* Edit story link. */
    if (Jonah::checkPermissions(Jonah::typeToPermName(Jonah::INTERNAL_CHANNEL), Horde_Perms::EDIT, $channel_id)) {
        $url = Horde::url('stories/edit.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
        $stories[$key]['edit_link'] = $url->link(array('title' => _("Edit story"))) . Horde::img('edit.png') . '</a>';
    }

    /* Delete story link. */
    if (Jonah::checkPermissions(Jonah::typeToPermName(Jonah::INTERNAL_CHANNEL), Horde_Perms::DELETE, $channel_id)) {
        $url = Horde::url('stories/delete.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
        $stories[$key]['delete_link'] = $url->link(array('title' => _("Delete story"))) . Horde::img('delete.png') . '</a>';
    }

    /* Comment counter. */
    if ($conf['comments']['allow'] &&
        $registry->hasMethod('forums/numMessages')) {
        try {
            $comments = $registry->call('forums/numMessages', array($stories[$key]['id'], 'jonah'));
        } catch (Exception $e) {}
        $stories[$key]['comments'] = $comments;
    }
}


/** @TODO It might be better using a new template for results? **/
$template = new Horde_Template();
$template->setOption('gettext', true);
if (isset($channel)) {
    $template->set('header', sprintf(_("Stories tagged with %s in %s"), $tag_name, htmlspecialchars($channel['channel_name'])));
} else {
    $template->set('header', sprintf(_("All stories tagged with %s"), $tag_name));
}
$template->set('listheaders', array(_("Story"), _("Date")));
$template->set('stories', $stories, true);
$template->set('read', true, true);
$template->set('comments', $conf['comments']['allow'] && $registry->hasMethod('forums/numMessages'), true);
$template->set('menu', Horde::menu());

// Buffer the notifications and send to the template
Horde::startBuffer();
$GLOBALS['notification']->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/stories/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
