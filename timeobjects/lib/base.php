<?php
/**
 * Base inclusion file
 *
 */
$rto_dir = dirname(__FILE__);

// Check for a prior definition of HORDE_BASE.
if (!defined('HORDE_BASE')) {
    /* Temporary fix - if horde does not live directly under the imp
     * directory, the HORDE_BASE constant should be defined in
     * imp/lib/base.local.php. */
    if (file_exists($rto_dir . '/base.local.php')) {
        include $rto_dir . '/base.local.php';
    } else {
        define('HORDE_BASE', $rto_dir . '/../..');
    }
}

/* Load the Horde Framework core, and set up inclusion paths. */
require_once HORDE_BASE . '/lib/core.php';
Horde_Autoloader::addClassPath($rto_dir);
Horde_Autoloader::addClassPattern('/^TimeObjects_/', $rto_dir);

/* Registry. */
$session_control = Horde_Util::nonInputVar('session_control');
if ($session_control == 'none') {
    $registry = &Registry::singleton(Registry::SESSION_NONE);
} elseif ($session_control == 'readonly') {
    $registry = &Registry::singleton(Registry::SESSION_READONLY);
} else {
    $registry = &Registry::singleton();
}

if (is_a(($pushed = $registry->pushApp('timeobjects', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}

if (!defined('TIMEOBJECTS_BASE')) {
    define('TIMEOBJECTS_BASE', $rto_dir . '/..');
}
