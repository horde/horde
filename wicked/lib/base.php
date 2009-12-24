<?php
/**
 * Wicked base inclusion file.
 *
 * This file brings in all of the dependencies that every Wicked
 * script will need and sets up objects that all scripts use.
 *
 * $Horde: wicked/lib/base.php,v 1.21 2009/07/13 20:05:59 slusarz Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('wicked', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    if ($e->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($e, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('WICKED_TEMPLATES', $registry->get('templates'));

// Find the base file path of Wicked.
if (!defined('WICKED_BASE')) {
    define('WICKED_BASE', dirname(__FILE__) . '/..');
}

// Notification system.
$notification = &Horde_Notification::singleton();
$notification->attach('status');

// Wicked base libraries.
require_once WICKED_BASE . '/lib/Wicked.php';
require_once WICKED_BASE . '/lib/Driver.php';
require_once WICKED_BASE . '/lib/Page.php';
$GLOBALS['wicked'] = Wicked_Driver::factory();

// Start compression.
if (!Horde_Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}
