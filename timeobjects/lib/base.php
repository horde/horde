<?php
/**
 * Base inclusion file
 *
 */

if (!defined('TIMEOBJECTS_BASE')) {
    define('TIMEOBJECTS_BASE', dirname(__FILE__) . '/..');
}

// Check for a prior definition of HORDE_BASE.
if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(TIMEOBJECTS_BASE . '/config/horde.local.php')) {
        include TIMEOBJECTS_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', TIMEOBJECTS_BASE . '/..');
    }
}

/* Load the Horde Framework core. */
require_once HORDE_BASE . '/lib/core.php';

/* Registry. */
$session_control = Horde_Util::nonInputVar('session_control');
if ($session_control == 'none') {
    $registry = new Horde_Registry(Horde_Registry::SESSION_NONE);
} elseif ($session_control == 'readonly') {
    $registry = new Horde_Registry(Horde_Registry::SESSION_READONLY);
} else {
    $registry = new Horde_Registry();
}

try {
    $registry->pushApp('timeobjects', array('logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('timeobjects', $e);
}
