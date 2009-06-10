<?php
/**
 * Chora base inclusion file.
 *
 * This file brings in all of the dependencies that every Chora script
 * will need, and sets up objects that all scripts use.
 */

$chora_dir = dirname(__FILE__);

// Check for a prior definition of HORDE_BASE.
if (!defined('HORDE_BASE')) {
    /* Temporary fix - if horde does not live directly under the imp
     * directory, the HORDE_BASE constant should be defined in
     * imp/lib/base.local.php. */
    if (file_exists($chora_dir . '/base.local.php')) {
        include $chora_dir . '/base.local.php';
    } else {
        define('HORDE_BASE', $chora_dir . '/../..');
    }
}

// Find the base file path of Chora.
if (!defined('CHORA_BASE')) {
    define('CHORA_BASE', $chora_dir . '/..');
}

// Load the Horde Framework core, and set up inclusion paths.
// No inclusion paths currently needed for Chora
require_once HORDE_BASE . '/lib/core.php';

// Registry
$registry = &Registry::singleton();
if (is_a(($pushed = $registry->pushApp('chora', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('CHORA_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Horde base libraries.
require_once 'Horde/Help.php';

// Chora base library.
require_once CHORA_BASE . '/lib/Chora.php';

// Initialize objects, path, etc.
Chora::initialize();

// Start compression, if requested.
Horde::compressOutput();
