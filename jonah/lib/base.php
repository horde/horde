<?php
/**
 * Jonah base inclusion file.
 *
 * $Horde: jonah/lib/base.php,v 1.63 2009/10/20 09:17:26 jan Exp $
 *
 * This file brings in all of the dependencies that every Jonah script
 * will need, and sets up objects that all scripts use.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
if (Horde_Util::nonInputVar('session_control') == 'none') {
    $registry = Horde_Registry::singleton(Horde_Registry::SESSION_NONE);
} elseif (Horde_Util::nonInputVar('session_control') == 'readonly') {
    $registry = Horde_Registry::singleton(Horde_Registry::SESSION_READONLY);
} else {
    $registry = Horde_Registry::singleton();
}

try {
    $registry->pushApp('jonah', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    if ($e->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($e, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('JONAH_TEMPLATES', $registry->get('templates'));

/* Notification system. */
$notification = &Horde_Notification::singleton();
$notification->attach('status');

/* Find the base file path of Jonah. */
if (!defined('JONAH_BASE')) {
    define('JONAH_BASE', dirname(__FILE__) . '/..');
}

/* Jonah base library. */
require_once JONAH_BASE . '/lib/Jonah.php';

/* Instantiate Jonah storage */
require_once JONAH_BASE . '/lib/Driver.php';
$GLOBALS['jonah_driver'] = Jonah_Driver::factory();

// Start compression.
if (!Horde_Util::nonInputVar('no_compress')) {
     Horde::compressOutput();
}
