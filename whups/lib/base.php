<?php
/**
 * Whups base inclusion file.
 *
 * This file brings in all of the dependencies that every Whups script will
 * need, and sets up objects that all scripts use.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Whups
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = Horde_Registry::singleton();

// Determine whups authentication type.
$authentication = Horde_Util::nonInputVar('whups_authentication');

try {
    $registry->pushApp('whups', array('check_perms' => ($authentication != 'none'), 'logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('whups', $e);
}

$conf = &$GLOBALS['conf'];
define('WHUPS_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Horde_Notification::singleton();
$notification->attach('status');

// Find the base file path of Whups.
if (!defined('WHUPS_BASE')) {
    define('WHUPS_BASE', dirname(__FILE__) . '/..');
}

// Whups base libraries.
require_once WHUPS_BASE . '/lib/Whups.php';
require_once WHUPS_BASE . '/lib/Driver.php';

// Horde libraries.
require_once 'Horde/Group.php';

// Form libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

// Start output compression.
Horde::compressOutput();

// Whups backend.
$GLOBALS['whups_driver'] = Whups_Driver::factory();
$GLOBALS['whups_driver']->initialise();
