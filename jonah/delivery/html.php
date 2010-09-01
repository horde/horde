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

require_once dirname(__FILE__) . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));
require JONAH_BASE . '/config/templates.php';

// TODO - check if a user, have button to add channel to their
// personal aggregated channel.

$news = Jonah_News::factory();

/* Get the id and format of the channel to display. */
$criteria = Horde_Util::nonInputVar('criteria');
if (!$criteria) {
    $criteria['channel_id'] = Horde_Util::getFormData('channel_id');
    $criteria['channel_format'] = Horde_Util::getFormData('format');
}

if (empty($criteria['channel_format'])) {
    // Select the default channel format
    $criteria['channel_format'] = key($templates);
}

/* Get requested channel. */
$channel = $news->getChannel($criteria['channel_id']);
if (is_a($channel, 'PEAR_Error')) {
    Horde::logMessage($channel, 'ERR');
    $notification->push(_("Invalid channel."), 'horde.error');
    Horde::url('delivery/index.php', true)->redirect();
}

$title = sprintf(_("HTML Delivery for \"%s\""), $channel['channel_name']);

$options = array();
foreach ($templates as $key => $info) {
    $options[] = '<option value="' . $key . '"' . ($key == $criteria['channel_format'] ? ' selected="selected"' : '') . '>' . $info['name'] . '</option>';
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

// Buffer the notifications and send to the template
Horde::startBuffer();
$GLOBALS['notification']->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/delivery/html.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
