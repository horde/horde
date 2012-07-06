<?php
/**
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('jonah', array('authentication' => 'none'));

$channel_id = Horde_Util::getFormData('channel_id');
$story_id = Horde_Util::getFormData('id');
if (!$story_id) {
    try {
        $story_id = $injector->getInstance('Jonah_Driver')->getLatestStoryId($channel_id);
    } catch (Exception $e) {
        $notification->push(sprintf(_("Error fetching story: %s"), $e->getMessage()), 'horde.warning');
        $page_output->header();
        require JONAH_TEMPLATES . '/menu.inc';
        $page_output->footer();
        exit;
    }
}

$params = array('registry' => &$registry,
                'notification' => &$notification,
                'channel_id' => $channel_id,
                'browser' => &$browser,
                'conf' => &$conf,
                'story_id' => $story_id);
$view = new Jonah_View_StoryView($params);
$view->run();
