<?php
/**
 * Shout base inclusion file.
 *
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @since   Shout 0.1
 * @package Shout
 */

if (!defined('SHOUT_BASE')) {
    define('SHOUT_BASE', dirname(__FILE__). '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(SHOUT_BASE. '/config/horde.local.php')) {
        include SHOUT_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', SHOUT_BASE . '/..');
    }
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

$registry = &Horde_Registry::singleton();
try {
    $registry->pushApp('shout', array('check_perms' => true, 'logintasks' => true));
} catch (Horde_Exception $e) {
    Horde::authenticationFailureRedirect('shout', $e);
}

$conf = &$GLOBALS['conf'];
@define('SHOUT_TEMPLATES', $registry->get('templates'));

// Ensure Shout is properly configured before use
$shout_configured = (@is_readable(SHOUT_BASE . '/config/conf.php'));
if (!$shout_configured) {
    Horde_Test::configFilesMissing('Shout', SHOUT_BASE, array('conf.php'));
}

$notification = Horde_Notification::singleton();
$notification->attach('status');

//// Shout base libraries.
//require_once SHOUT_BASE . '/lib/Shout.php';
//require_once SHOUT_BASE . '/lib/Driver.php';
//
//// Form libraries.
//require_once 'Horde/Form.php';
//require_once 'Horde/Form/Renderer.php';
//
//// Variable handling libraries
//require_once 'Horde/Variables.php';
//require_once 'Horde/Text/Filter.php';
//
//// UI classes.
//require_once 'Horde/UI/Tabs.php';

$shout_contexts = Shout_Driver::factory('storage');
$shout_extensions = Shout_Driver::factory('extensions');
$shout_devices = Shout_Driver::factory('devices');

$context = Horde_Util::getFormData('context');
$section = Horde_Util::getFormData('section');

try {
    $contexts = $shout_contexts->getContexts();
} catch (Shout_Exception $e) {
    $notification->push($e);
    $contexts = false;
}

if (count($contexts) == 1) {
    // Default to the user's only context
    if (!empty($context) && $context != $contexts[0]) {
        $notification->push(_("You do not have permission to access that context."), 'horde.error');
    }
    $context = $contexts[0];
} elseif (!empty($context) && !in_array($context, $contexts)) {
    $notification->push(_("You do not have permission to access that context."), 'horde.error');
    $context = false;
} elseif (!empty($context)) {
    $notification->push("Please select a context to continue.", 'horde.info');
    $context = false;
}

$_SESSION['shout']['context'] = $context;
