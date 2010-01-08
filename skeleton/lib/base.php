<?php
/**
 * Skeleton base application file.
 *
 * This file brings in all of the dependencies that every Skeleton script will
 * need, and sets up objects that all scripts use.
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

// // Load the Horde Framework core.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('skeleton', array('check_perms' => true, 'logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('skeleton', $e);
}

$conf = &$GLOBALS['conf'];
@define('SKELETON_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = Horde_Notification::singleton();
$notification->attach('status');

// Start output compression.
Horde::compressOutput();
