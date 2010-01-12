<?php
/**
 * Crumb base application file.
 *
 * Copyright 2008-2010 The Horde Project <http://www.horde.org>
 *
 * This file brings in all of the dependencies that every Crumb script will
 * need, and sets up objects that all scripts use.
 *
 * @author Ben Klang <ben@alkaloid.net>
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
    $registry->pushApp('crumb', array('logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('crumb', $e);
}
$conf = &$GLOBALS['conf'];
@define('CRUMB_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = Horde_Notification::singleton();
$notification->attach('status');

// Define the base file path of Crumb.
@define('CRUMB_BASE', dirname(__FILE__) . '/..');

// Crumb driver
$crumb_driver = Crumb_Driver::factory();

// Start output compression.
Horde::compressOutput();
