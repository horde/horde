<?php
/**
 * Jeta base inclusion file.
 *
 * This file brings in all of the dependencies that every web ssh script
 * will need, and sets up objects that all scripts use.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

// Load the Horde Framework core.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('jeta', array('logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('jeta', $e);
}

$conf = &$GLOBALS['conf'];
define('JETA_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = Horde_Notification::singleton();
$notification->attach('status');
