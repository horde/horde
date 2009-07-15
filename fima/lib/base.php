<?php
/**
 * Fima base application file.
 *
 * $Horde: fima/lib/base.php,v 1.0 2008/04/25 00:15:28 trt Exp $
 *
 * This file brings in all of the dependencies that every Fima script will
 * need, and sets up objects that all scripts use.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an auto_prepend_file
// definition for site customization).
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$session_control = Horde_Util::nonInputVar('session_control');
if ($session_control == 'none') {
    $registry = Horde_Registry::singleton(HORDE_SESSION_NONE);
} elseif ($session_control == 'readonly') {
    $registry = Horde_Registry::singleton(HORDE_SESSION_READONLY);
} else {
    $registry = Horde_Registry::singleton();
}

try {
    $registry->pushApp('fima', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticationFailureRedirect('fima', $e);
}
$conf = &$GLOBALS['conf'];
@define('FIMA_TEMPLATES', $registry->get('templates'));

// Find the base file path of Fima.
if (!defined('FIMA_BASE')) {
    @define('FIMA_BASE', dirname(__FILE__) . '/..');
}

// Notification system.
$notification = Horde_Notification::singleton();
$notification->attach('status');

// Fima base library
require_once FIMA_BASE . '/lib/Driver.php';

// Horde libraries.
require_once 'Horde/History.php';

// Start output compression.
Horde::compressOutput();

// Set the timezone variable.
Horde_Nls::setTimeZone();

// Create a share instance.
$GLOBALS['fima_shares'] = Horde_Share::singleton($registry->getApp());

Fima::initialize();
