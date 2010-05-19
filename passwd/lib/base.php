<?php
/**
 * Passwd base inclusion file.
 *
 * This file brings in all of the dependencies that every PASSWD script will
 * need, and sets up objects that all scripts use.
 *
 * $Horde: passwd/lib/base.php,v 1.33.2.7 2009/10/19 23:34:20 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Passwd
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
if (is_a(($pushed = $registry->pushApp('passwd', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
@define('PASSWD_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Find the base file path of Passwd
@define('PASSWD_BASE', dirname(__FILE__) . '/..');

// Passwd base library.
require_once PASSWD_BASE . '/lib/Passwd.php';

// Start compression.
if (!Util::nonInputVar('no_compress')) {
     Horde::compressOutput();
}

// Horde libraries.
require_once 'Horde/Help.php';
require_once 'Horde/Secret.php';
