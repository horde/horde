<?php
/**
 * Kronolith base inclusion file.
 *
 * This file brings in all of the dependencies that every Kronolith
 * script will need, and sets up objects that all scripts use.
 *
 * The following global variables are used:
 * <pre>
 * $kronolith_authentication - The type of authentication to use:
 *   'none'  - Do not authenticate
 *   [DEFAULT] - Authenticate; on failure redirect to login screen
 * $kronolith_session_control - Sets special session control limitations:
 *   'none' - Do not start a session
 *   'readonly' - Start session readonly
 *   [DEFAULT] - Start read-write session
 * </pre>
 *
 * @package Kronolith
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

/* Load the Horde Framework core. */
require_once HORDE_BASE . '/lib/core.php';

/* Registry. */
$s_ctrl = 0;
switch (Horde_Util::nonInputVar('kronolith_session_control')) {
case 'none':
    $s_ctrl = Horde_Registry::SESSION_NONE;
    break;

case 'readonly':
    $s_ctrl = Horde_Registry::SESSION_READONLY;
    break;
}
$registry = Horde_Registry::singleton($s_ctrl);

$authentication = Horde_Util::nonInputVar('kronolith_authentication');
try {
    $registry->pushApp('kronolith', ($authentication != 'none'));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticationFailureRedirect('kronolith', $e);
}
$conf = &$GLOBALS['conf'];
define('KRONOLITH_TEMPLATES', $registry->get('templates'));

/* Notification system. */
$notification = Horde_Notification::singleton();
$GLOBALS['kronolith_notify'] = $notification->attach('status', null, 'Kronolith_Notification_Listener_Status');

/* Start compression. */
Horde::compressOutput();

/* Set the timezone variable, if available. */
Horde_Nls::setTimeZone();

/* Create a share instance. */
$GLOBALS['kronolith_shares'] = Horde_Share::singleton($registry->getApp());

Kronolith::initialize();
