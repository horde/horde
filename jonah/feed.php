<?php
/**
 * Script to handle requests for html delivery of stories.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$jonah = Horde_Registry::appInit('jonah', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));
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
    $criteria['channel_id'] = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannelId($criteria['feed']);
}

if (empty($criteria['channel_id'])) {
    $notification->push(_("No valid feed name or ID requested."), 'horde.error');
} else {
    $stories = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStories($criteria);
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
$template->set('menu', Horde::menu());

// Buffer the notifications and send to the template
Horde::startBuffer();
$GLOBALS['notification']->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/delivery/html.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
