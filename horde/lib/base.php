<?php
/**
 * Horde base inclusion file.
 *
 * This file brings in all of the dependencies that Horde framework-level
 * scripts will need, and sets up objects that all scripts use.
 *
 * The following global variables are used:
 * <pre>
 * $horde_authentication - The type of authentication to use:
 *   'ignore' - Authenticate; on no auth, ignore error
 *   'none' - Do not authenticate
 *   [DEFAULT] - Authenticate; on no auth, redirect to login screen
 * $horde_no_compress - Controls whether the page should be compressed
 * $horde_no_logintasks - Don't perform logintasks (logintasks are never
 *                        performend if $horde_authentication == 'none')
 * $horde_session_control - Sets special session control limitations:
 *   'none' - Do not start a session
 *   'readonly' - Start session readonly
 *   [DEFAULT] - Start read/write session
 * </pre>
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

// Load the Horde Framework core, and set up inclusion paths.
require_once dirname(__FILE__) . '/core.php';

// Registry.
$s_ctrl = 0;
switch (Horde_Util::nonInputVar('horde_session_control')) {
case 'none':
    $s_ctrl = Horde_Registry::SESSION_NONE;
    break;

case 'readonly':
    $s_ctrl = Horde_Registry::SESSION_READONLY;
    break;
}
$registry = Horde_Registry::singleton($s_ctrl);

$authentication = Horde_Util::nonInputVar('horde_authentication');
try {
    $registry->pushApp('horde', array('check_perms' => ($authentication != 'none'), 'logintasks' => (($authentication != 'none') && !Horde_Util::nonInputVar('horde_no_logintasks'))));
} catch (Horde_Exception $e) {
    if (($e->getCode() == Horde_Registry::AUTH_FAILURE) &&
        ($authentication == 'ignore')) {
        /* Push app without doing checks. */
        $registry->pushApp('horde', array('check_perms' => false));
    } else {
        Horde_Auth::authenticateFailure('horde', $e);
    }
}
$conf = &$GLOBALS['conf'];
@define('HORDE_TEMPLATES', $registry->get('templates'));

// Notification System.
$notification = Horde_Notification::singleton();
$notification->attach('status');

// Compress output
if (!Horde_Util::nonInputVar('horde_no_compress')) {
    Horde::compressOutput();
}
