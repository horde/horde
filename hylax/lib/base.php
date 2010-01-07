<?php
/**
 * Hylax base inclusion file.
 *
 * This file brings in all of the dependencies that every Hylax script will
 * need, and sets up objects that all scripts use.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: incubator/hylax/lib/base.php,v 1.16 2009/07/13 20:05:46 slusarz Exp $
 */

if (!defined('HYLAX_BASE')) {
    define('HYLAX_BASE', dirname(__FILE__). '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(HYLAX_BASE. '/config/horde.local.php')) {
        include HYLAX_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', HYLAX_BASE . '/..');
    }
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('hylax', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    if ($e->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($e, __FILE__, __LINE__, false);
}

$conf = &$GLOBALS['conf'];
@define('HYLAX_TEMPLATES', $registry->get('templates'));

/* Notification system. */
$notification = &Horde_Notification::singleton();
$notification->attach('status');

/* Find the base file path of Hylax. */
@define('HYLAX_BASE', dirname(__FILE__) . '/..');

/* Hylax base libraries. */
require_once HYLAX_BASE . '/lib/Hylax.php';
require_once HYLAX_BASE . '/lib/Driver.php';
require_once HYLAX_BASE . '/lib/Storage.php';

/* Hylax Driver */
$gateway = &Hylax_Driver::singleton($conf['fax']['driver'], $conf['fax']['params']);

/* Hylax storage driver. */
$hylax_storage = &Hylax_Storage::singleton('sql', $conf['sql']);

/* Start compression, if requested. */
Horde::compressOutput();
