<?php
/**
 * Gollem base inclusion file. This file brings in all of the
 * dependencies that every Gollem script will need, and sets up
 * objects that all scripts use.
 *
 * The following variables, defined in the script that calls this one, are
 * used:
 *   $gollem_authentication   - The authentication mode to use.
 *   $gollem_session_control  - Sets special session control limitations.
 *
 * This file creates the following global variables:
 *   $gollem_backends - A link to the current list of available backends
 *   $gollem_be - A link to the current backend parameters in the session
 *   $gollem_vfs - A link to the current VFS object for the active backend
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
if (Horde_Util::nonInputVar('gollem_session_control') == 'readonly') {
    $registry = Horde_Registry::singleton(Horde_Registry::SESSION_READONLY);
} else {
    $registry = Horde_Registry::singleton();
}

try {
    $registry->pushApp('gollem', array('check_perms' => (Horde_Util::nonInputVar('gollem_authentication') != 'none'), 'logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('gollem', $e);
}
$conf = &$GLOBALS['conf'];
define('GOLLEM_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = Horde_Notification::singleton();
$notification->attach('status');

// Start compression.
Horde::compressOutput();

// Set the global $gollem_be variable to the current backend's parameters.
if (empty($_SESSION['gollem']['backend_key'])) {
    $GLOBALS['gollem_be'] = null;
} else {
    $GLOBALS['gollem_be'] = &$_SESSION['gollem']['backends'][$_SESSION['gollem']['backend_key']];
}

// Load the backend list.
Gollem::loadBackendList();
