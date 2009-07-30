<?php
/**
 * This file brings in all of the dependencies that every Ansel script will need
 * and sets up objects that all scripts use.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('ansel', array('check_perms' => (Horde_Util::nonInputVar('ansel_authentication') != 'none'), 'logintasks' => true));
} catch (Horde_Exception $e) {
        Horde_Auth::authenticationFailureRedirect('ansel', $e);
}

$conf = &$GLOBALS['conf'];
if (!defined('ANSEL_TEMPLATES')) {
    define('ANSEL_TEMPLATES', $registry->get('templates'));
}

// Notification system.
$GLOBALS['notification'] = Horde_Notification::singleton();
$GLOBALS['notification']->attach('status');

// Find the base file path of Ansel.
if (!defined('ANSEL_BASE')) {
    define('ANSEL_BASE', dirname(__FILE__) . '/..');
}

// Ansel base libraries.
require_once ANSEL_BASE . '/lib/Ansel.php';

// Create a cache object if we need it.
if ($conf['ansel_cache']['usecache']) {
    $GLOBALS['cache'] = Horde_Cache::singleton($conf['cache']['driver'],
                                               Horde::getDriverConfig('cache', $conf['cache']['driver']));
}

// Create db, share, and vfs instances.
$GLOBALS['ansel_db'] = Ansel::getDb();
if (is_a($GLOBALS['ansel_db'], 'PEAR_Error')) {
    Horde::fatal($GLOBALS['ansel_db'], __FILE__, __LINE__, false);
}
$GLOBALS['ansel_storage'] = new Ansel_Storage();
$GLOBALS['ansel_vfs'] = Ansel::getVFS();

// Get list of available styles for this client.
$GLOBALS['ansel_styles'] = Ansel::getAvailableStyles();
if ($logger = Horde::getLogger()) {
    $GLOBALS['ansel_vfs']->setLogger($logger, $GLOBALS['conf']['log']['priority']);
}
