<?php
/**
 * ajax.php defines an set of brower procedure mechanisms
 * for the Shout application.
 *
 * Copyright 2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @since   Shout 0.1
 * @package Shout
 */

// Need to load Horde_Util:: to give us access to Horde_Util::getPathInfo().
require_once dirname(__FILE__) . '/lib/Application.php';
$action = basename(Horde_Util::getPathInfo());
if (empty($action)) {
    // This is the only case where we really don't return anything, since
    // the frontend can be presumed not to make this request on purpose.
    // Other missing data cases we return a response of boolean false.
    exit;
}

try {
    new Shout_Application();
} catch (Horde_Exception $e) {
    /* Handle session timeouts when they come from an AJAX request. */
    if (($e->getCode() == Horde_Registry::AUTH_FAILURE) &&
        ($action != 'LogOut')) {
        //FIXME: The below is certain to break since it relies on classes I did
        //       not yet copy from IMP.
        $notification = Horde_Notification::singleton();
        $shout_notify = $notification->attach('status', array('viewmode' => 'dimp'), 'Shout_Notification_Listener_Status');
        $notification->push(str_replace('&amp;', '&', Horde_Auth::getLogoutUrl(array('reason' => Horde_Auth::REASON_SESSION))), 'shout.timeout', array('content.raw'));
        Horde::sendHTTPResponse(Horde::prepareResponse(null, $shout_notify), 'json');
        exit;
    }

    Horde_Auth::authenticateFailure('shout', $e);
}

