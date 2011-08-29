<?php
/**
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chris Bowlby <cbowlby@tenthpowertech.com>
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('sam', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    if ($e->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($e, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
@define('SAM_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Horde_Notification::singleton();
$notification->attach('status');

// Redirect the user to the Horde login page if they haven't
// authenticated.
if (!Horde_Auth::isAuthenticated()) {
    Horde::authenticationFailureRedirect();
}

// Find the base file path of SAM.
@define('SAM_BASE', dirname(__FILE__) . '/..');

// Sam base library.
require_once SAM_BASE . '/lib/SAM.php';

// Horde libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

// Start compression.
Horde::compressOutput();

// Load the storage driver. It appears in the global variable
// $sam_driver.
require_once SAM_BASE . '/lib/Driver.php';
$backend = Sam::getBackend();
$user = Sam::mapUser($backend['hordeauth']);
$GLOBALS['sam_driver'] = &SAM_Driver::singleton($backend['driver'], $user,
                                                $backend['params']);
