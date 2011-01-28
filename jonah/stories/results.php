<?php
/**
 * Display list of articles that match a tag query.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
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

/* Make sure we actually requested a tag search */
$tag_id = Horde_Util::getFormData('tag_id');
if (empty($tag_id)) {
    $notification->push(_("No tag requested."), 'horde.error');
    Horde::url('channels/index.php', true)->redirect();
}

$params = array('registry' => $registry,
                'notification' => $notification,
                'prefs' => $prefs,
                'conf' => $conf,
                'tag_id' => $tag_id,
                'channel_id' => Horde_Util::getFormData('channel_id'));
$view = new Jonah_View_TagSearchList($params);
$view->run();
