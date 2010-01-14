<?php
/**
 * The Agora base inclusion library.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths and autoloading.
require_once HORDE_BASE . '/lib/core.php';

/* Set up the registry. */
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('agora');
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('agora', $e);
}
$conf = &$GLOBALS['conf'];
define('AGORA_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = Horde_Notification::singleton();
$notification->attach('status');

/* Agora base library. */
if (!defined('AGORA_BASE')) {
    define('AGORA_BASE', dirname(__FILE__) . '/..');
}

require_once AGORA_BASE . '/lib/Agora.php';
require_once AGORA_BASE . '/lib/Messages.php';
require_once AGORA_BASE . '/lib/View.php';

// Start compression.
if (!Horde_Util::nonInputVar('no_compress')) {
     Horde::compressOutput();
}
