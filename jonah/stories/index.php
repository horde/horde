<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('jonah');

/* Redirect to the news index if no channel_id is specified. */
$channel_id = Horde_Util::getFormData('channel_id');
if (empty($channel_id)) {
    $notification->push(_("No channel requested."), 'horde.error');
    header('Location: ' . Horde::url('channels/index.php', true));
    exit;
}

$params = array('registry' => &$registry,
                'notification' => &$notification,
                'prefs' => &$prefs,
                'conf' => &$conf,
                'channel_id' => $channel_id);
$view = new Jonah_View_StoryList($params);
$view->run();
