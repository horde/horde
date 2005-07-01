<?php
/**
 * Shout base inclusion file.
 *
 * $Horde: shout/lib/base.php,v 0.1 2005/06/29 01:00:23 ben Exp $
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

// Registry.
$registry = &Registry::singleton();
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

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Shout base libraries.
require_once SHOUT_BASE . '/lib/Shout.php';
require_once SHOUT_BASE . '/lib/Driver.php';

// Form libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

// UI classes.
require_once 'Horde/UI/Tabs.php';

$GLOBALS['shout'] = &Shout_Driver::singleton();

// Horde libraries.
require_once 'Horde/Help.php';