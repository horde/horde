<?php
/**
 * Fima base application file.
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
    $registry = new Horde_Registry(HORDE_SESSION_NONE);
} elseif ($session_control == 'readonly') {
    $registry = new Horde_Registry(HORDE_SESSION_READONLY);
} else {
    $registry = new Horde_Registry();
}

try {
    $registry->pushApp('fima', array('logintasks' => true));
} catch (Horde_Exception $e) {
    $registry->authenticateFailure('fima', $e);
}
$conf = &$GLOBALS['conf'];
@define('FIMA_TEMPLATES', $registry->get('templates'));

// Find the base file path of Fima.
if (!defined('FIMA_BASE')) {
    @define('FIMA_BASE', dirname(__FILE__) . '/..');
}

// Fima base library
require_once FIMA_BASE . '/lib/Driver.php';

// Start output compression.
Horde::compressOutput();

// Set the timezone variable.
$registry->setTimeZone();

// Create a share instance.
$GLOBALS['fima_shares'] = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();

Fima::initialize();
