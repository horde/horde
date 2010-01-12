<?php
/**
 * Skoli base application file.
 *
 * This file brings in all of the dependencies that every Skoli script will
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
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('skoli', array('check_perms' => (Horde_Util::nonInputVar('skoli_authentication') != 'none'), 'logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('skoli', $e);
}
$conf = &$GLOBALS['conf'];
@define('SKOLI_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = Horde_Notification::singleton();
$notification->attach('status');

// Define the base file path of Skoli.
@define('SKOLI_BASE', dirname(__FILE__) . '/..');

// Start output compression.
Horde::compressOutput();

// Create a share instance.
$GLOBALS['skoli_shares'] = Horde_Share::singleton($registry->getApp());

Skoli::initialize();
