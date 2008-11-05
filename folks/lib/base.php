<?php
/**
 * Folks base application file.
 *
 * $Horde: folks/lib/base.php,v 1.16 2007-02-21 10:25:28 jan Exp $
 *
 * This file brings in all of the dependencies that every Folks script will
 * need, and sets up objects that all scripts use.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an auto_prepend_file
// definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths and autoloading.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/Loader.php';

// Registry.
$registry = &Registry::singleton();
if (($pushed = $registry->pushApp('folks', !defined('AUTH_HANDLER'))) instanceof PEAR_Error) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('FOLKS_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Define the base file path of Folks.
if (!defined('FOLKS_BASE')) {
    define('FOLKS_BASE', dirname(__FILE__) . '/..');
}

// Folks base library
require_once FOLKS_BASE . '/lib/Folks.php';
require_once FOLKS_BASE . '/lib/Driver.php';
$GLOBALS['folks_driver'] = Folks_Driver::factory();

// Cache
$GLOBALS['cache'] = &Horde_Cache::singleton($GLOBALS['conf']['cache']['driver'],
                                            Horde::getDriverConfig('cache', $GLOBALS['conf']['cache']['driver']));

// Update user online status
$GLOBALS['folks_driver']->updateOnlineStatus();

// Start output compression.
if (!Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}