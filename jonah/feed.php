<?php
/**
 * Script to handle requests for html delivery of stories.
 *
 * $Horde: jonah/feed.php,v 1.6 2009/06/10 05:24:46 slusarz Exp $
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Jan Schneider <jan@horde.org>
 */

$session_control = 'readonly';
@define('AUTH_HANDLER', true);
require_once dirname(__FILE__) . '/lib/Application.php';
$jonah = Horde_Registry::appInit('jonah');
require JONAH_BASE . '/config/templates.php';

/* Get the id and format of the feed to display. */
$criteria = Horde_Util::nonInputVar('criteria');
if (empty($criteria['channel_format'])) {
    // Select the default channel format
    $criteria['channel_format'] = key($templates);
}

$options = array();
foreach ($templates as $key => $info) {
    $options[] = '<option value="' . $key . '"' . ($key == $criteria['channel_format'] ? ' selected="selected"' : '') . '>' . $info['name'] . '</option>';
}

if (empty($criteria['channel_id']) && !empty($criteria['feed'])) {
    $criteria['channel_id'] = $jonah_driver->getChannelId($criteria['feed']);
}

if (empty($criteria['channel_id'])) {
    $notification->push(_("No valid feed name or ID requested."), 'horde.error');
} else {
    $stories = $jonah_driver->getStories($criteria);
}

if (!empty($stories)) {
    die(print_r($stories, true));
}

$template = new Horde_Template();
$template->setOption('gettext', 'true');
$template->set('url', Horde::selfUrl());
$template->set('session', Horde_Util::formInput());
$template->set('channel_id', $criteria['channel_id']);
$template->set('channel_name', $channel['channel_name']);
$template->set('format', $criteria['channel_format']);
$template->set('options', $options);
$template->set('stories', $news->renderChannel($criteria['channel_id'], $criteria['channel_format']));
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/delivery/html.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
