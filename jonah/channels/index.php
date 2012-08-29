<?php
/**
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('jonah', array(
    'permission' => array('jonah:news', Horde_Perms::EDIT)
));

$have_news = Jonah::getAvailableTypes();
if (empty($have_news)) {
    $notification->push(_("News is not enabled."), 'horde.warning');

    $url = Horde::url('index.php');
    header('Location: ' . $url);
    exit;
}

$params = array('notification' => &$notification,
                'prefs' => &$prefs,
                'registry' => &$registry
          );

$view = new Jonah_View_ChannelList($params);
$view->run();
