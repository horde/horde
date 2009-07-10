<?php
/**
 * Skoli base application file.
 *
 * $Horde: skoli/lib/base.php,v 0.1 $
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
    $registry->pushApp('skoli', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    if ($e->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($e, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
@define('SKOLI_TEMPLATES', $registry->get('templates'));

// Horde framework libraries.
require_once 'Horde/History.php';

// Notification system.
$notification = &Horde_Notification::singleton();
$notification->attach('status');

// Define the base file path of Skoli.
@define('SKOLI_BASE', dirname(__FILE__) . '/..');

// Skoli base library
require_once SKOLI_BASE . '/lib/Skoli.php';
require_once SKOLI_BASE . '/lib/Driver.php';

// Start output compression.
Horde::compressOutput();

// Create a share instance.
require_once 'Horde/Share.php';
$GLOBALS['skoli_shares'] = &Horde_Share::singleton($registry->getApp());

Skoli::initialize();
