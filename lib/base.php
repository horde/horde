<?php
/**
 * Shout base inclusion file.
 *
 * $Id$
 *
 * This file brings in all of the dependencies that every Shout
 * script will need and sets up objects that all scripts use.
 */


// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/../..');
}
// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

$registry = &Horde_Registry::singleton();
if (is_a(($pushed = $registry->pushApp('shout', !defined('AUTH_HANDLER'))),
'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
@define('SHOUT_TEMPLATES', $registry->get('templates'));

// Find the base file path of Shout.
@define('SHOUT_BASE', dirname(__FILE__) . '/..');

// Ensure Shout is properly configured before use
$shout_configured = (@is_readable(SHOUT_BASE . '/config/conf.php') &&
                     @is_readable(SHOUT_BASE . '/config/defines.php') &&
                     @is_readable(SHOUT_BASE . '/config/applist.xml'));# &&
                     #@is_readable(SHOUT_BASE . '/config/prefs.php'));
if (!$shout_configured) {
    require SHOUT_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Shout', SHOUT_BASE,
        array('conf.php', 'defines.php', 'applist.xml'));
        #, 'prefs.php'));
}

$notification = &Horde_Notification::singleton();
$notification->attach('status');

// Shout base libraries.
require_once SHOUT_BASE . '/lib/Shout.php';
require_once SHOUT_BASE . '/lib/Driver.php';

// Form libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

// Variable handling libraries
require_once 'Horde/Variables.php';
require_once 'Horde/Text/Filter.php';

// UI classes.
require_once 'Horde/UI/Tabs.php';

$shout = Shout_Driver::singleton();

// Horde libraries.
require_once 'Horde/Help.php';

$context = Horde_Util::getFormData('context');
$section = Horde_Util::getFormData('section');

$contexts = $shout->getContexts();

// Check that we are properly initialized
if (is_a($contexts, 'PEAR_Error')) {
    $notification->push($contexts);
    $contexts = false;
} elseif (count($contexts) == 1) {
    // Default to the user's only context
    $context = $contexts[0];
} elseif (!$context) {
    // Attempt to locate the user's "home" context
    $context = $shout->getHomeContext();
    if (is_a($context, 'PEAR_Error')) {
        $notification->push($context);
    }
    $context = '';
}

// We've passed the initialization tests.  This flag allows other pages to run.
$SHOUT_RUNNING = true;
