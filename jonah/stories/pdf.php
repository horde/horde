<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Jonah
 */
require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('jonah', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));

$params = array('registry' => &$registry,
                'notification' => &$notification,
                'story_id' => Horde_Util::getFormData('id'),
                'browser' => &$browser,
                'channel_id' => Horde_Util::getFormData('channel_id'));
$view = new Jonah_View_StoryPdf($params);
$view->run();
