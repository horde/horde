<?php
/**
 * Chora base inclusion file.
 *
 * This file brings in all of the dependencies that every Chora script
 * will need, and sets up objects that all scripts use.
 *
 * The following global variables are used:
 *   $no_compress  -  Controls whether the page should be compressed
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

// Load the Horde Framework core.
require_once HORDE_BASE . '/lib/core.php';

// Registry
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('chora', array('logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('chora', $e);
}
$conf = &$GLOBALS['conf'];
define('CHORA_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = Horde_Notification::singleton();
$notification->attach('status');

// Initialize objects, path, etc.
Chora::initialize();

// Start compression.
if (!Horde_Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}
