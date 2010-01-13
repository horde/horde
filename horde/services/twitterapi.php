<?php
/**
 * Callback page for Twitter integration.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * This file is named 'twitterapi.php' rather than 'twitter.php' to avoid file
 * inclusion path problems and collisions on case-insensitive filesystems.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

$twitter = new Services_Twitter($_SESSION['horde']['twitterblock']['username'],
                                $_SESSION['horde']['twitterblock']['password']);

// Require the actions to be POST only since following them
// could change the user's state.
$action = Horde_Util::getPost('actionID');

switch ($action) {
case 'updateStatus':
    try {
        $result = $twitter->statuses->update(Horde_Util::getPost('statusText'));
		Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_DEBUG);
		$notification->push(_("Status successfully updated."), 'horde.success');
    } catch (Services_Twitter_Exception $e) {
    	$error = $e->getMessage();
		Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
		$notification->push($error);
    }
}

$notification->notify();
