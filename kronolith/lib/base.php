<?php
/**
 * Kronolith base inclusion file.
 *
 * This file brings in all of the dependencies that every Kronolith
 * script will need, and sets up objects that all scripts use.
 *
 * The following variables, defined in the script that calls this one, are
 * used:
 * - $session_control - Sets special session control limitations
 *
 * @package Kronolith
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

/* Load the Horde Framework core. */
require_once HORDE_BASE . '/lib/core.php';

/* Registry. */
$session_control = Horde_Util::nonInputVar('session_control');
if ($session_control == 'none') {
    $registry = Horde_Registry::singleton(Horde_Registry::SESSION_NONE);
} elseif ($session_control == 'readonly') {
    $registry = Horde_Registry::singleton(Horde_Registry::SESSION_READONLY);
} else {
    $registry = Horde_Registry::singleton();
}

try {
    $registry->pushApp('kronolith', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticationFailureRedirect('kronolith', $e);
}
$conf = &$GLOBALS['conf'];
define('KRONOLITH_TEMPLATES', $registry->get('templates'));

/* Notification system. */
$notification = Horde_Notification::singleton();
$GLOBALS['kronolith_notify'] = $notification->attach('status', null, 'Kronolith_Notification_Listener_Status');

/* Start compression, if requested. */
Horde::compressOutput();

/* Set the timezone variable, if available. */
Horde_Nls::setTimeZone();

/* Create a share instance. */
$GLOBALS['kronolith_shares'] = Horde_Share::singleton($registry->getApp());

Kronolith::initialize();
