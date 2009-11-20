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
 * $no_compress - Controls whether the page should be compressed
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

try {
    $registry->pushApp('kronolith', array('check_perms' => (Horde_Util::nonInputVar('kronolith_authentication') != 'none'), 'logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('kronolith', $e);
}
$conf = &$GLOBALS['conf'];
define('KRONOLITH_TEMPLATES', $registry->get('templates'));

/* For now, autoloading the Content_* classes depend on there being a registry
 * entry for the 'content' application that contains at least the fileroot
 * entry. */
Horde_Autoloader::addClassPattern('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/');
if (!class_exists('Content_Tagger')) {
    throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the registry entry for the Content system is present.');
}

/* Notification system. */
$GLOBALS['notification'] = Horde_Notification::singleton();
$GLOBALS['kronolith_notify'] = $GLOBALS['notification']->attach('status', null, 'Kronolith_Notification_Listener_Status');

/* Start compression. */
if (!Horde_Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}

/* Set the timezone variable, if available. */
Horde_Nls::setTimeZone();

/* Create a share instance. */
$GLOBALS['kronolith_shares'] = Horde_Share::singleton($registry->getApp());

Kronolith::initialize();
