<?php
/**
 * Chora base inclusion file.
 *
 * This file brings in all of the dependencies that every Chora script
 * will need, and sets up objects that all scripts use.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Find the base file path of Chora.
if (!defined('CHORA_BASE')) {
    define('CHORA_BASE', dirname(__FILE__) . '/..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/Autoloader.php';

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

// Cache.
$cache = &Horde_Cache::singleton($conf['cache']['driver'], Horde::getDriverConfig('cache', $conf['cache']['driver']));

// Horde base libraries.
require_once 'Horde/Text.php';
require_once 'Horde/Help.php';

// Chora libraries and config.
if (is_callable(array('Horde', 'loadConfiguration'))) {
    $sourceroots = Horde::loadConfiguration('sourceroots.php', 'sourceroots');
} else {
    require_once CHORA_BASE . '/config/sourceroots.php';
}
require_once CHORA_BASE . '/lib/Chora.php';

// Initialize objects, path, etc.
Chora::initialize();

// Start compression, if requested.
Horde::compressOutput();
