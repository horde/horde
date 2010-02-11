<?php
/**
 * Beatnik base inclusion file.
 *
 * Copyright 2005-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * This file brings in all of the dependencies that every Beatnik
 * script will need and sets up objects that all scripts use.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Beatnik
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}
// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = new Horde_Registry();

try {
    $registry->pushApp('beatnik', array('check_perms' => (Horde_Util::nonInputVar('beatnik_authentication') != 'none')));
} catch (Horde_Exception $e) {
    if ($e->getCode() == Horde_Registry::PERMISSION_DENIED) {
        Horde_Auth::authenticateFailure('beatnik', $e);
    }
    Horde::fatal($e, __FILE__, __LINE__, false);
}

$conf = &$GLOBALS['conf'];
define('BEATNIK_TEMPLATES', $registry->get('templates'));

// Find the base file path of Beatnik.
if (!defined('BEATNIK_BASE')) {
    define('BEATNIK_BASE', dirname(__FILE__) . '/..');
}

// Beatnik base libraries.
require_once BEATNIK_BASE . '/lib/Beatnik.php';
require_once BEATNIK_BASE . '/lib/Driver.php';

try {
    $GLOBALS['beatnik_driver'] = Beatnik_Driver::factory();
} catch (Exception $e) {
    Horde::fatal($e, __FILE__, __LINE__);
}

// Get a list of domains to work with
$domains = $GLOBALS['beatnik_driver']->getDomains();

// Jump to new domain
if (Horde_Util::getFormData('curdomain') !== null && !empty($domains)) {
    try {
        $domain = $GLOBALS['beatnik_driver']->getDomain(Horde_Util::getFormData('curdomain'));
    } catch (Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
        $domain = $domains[0];
    }

    $_SESSION['beatnik']['curdomain'] = $domain;
}

// Determine if the user should see basic or advanced options
if (!isset($_SESSION['beatnik']['expertmode'])) {
    $_SESSION['beatnik']['expertmode'] = false;
} elseif (Horde_Util::getFormData('expertmode') == 'toggle') {
    if ($_SESSION['beatnik']['expertmode']) {
        $notification->push(_('Expert Mode off'), 'horde.message');
        $_SESSION['beatnik']['expertmode'] = false;
    } else {
        $notification->push(_('Expert Mode ON'), 'horde.warning');
        $_SESSION['beatnik']['expertmode'] = true;
    }
}

// Initialize the page marker
if (!isset($_SESSION['beatnik']['curpage'])) {
    $_SESSION['beatnik']['curpage'] = 0;
}

// Start output compression.
if (!Horde_Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}

