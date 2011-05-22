<?php
/**
 * Ulaform base inclusion file.
 *
 * This file brings in all of the dependencies that every Ulaform script will
 * need, and sets up objects that all scripts use.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: ulaform/lib/base.php,v 1.33 2009-07-13 20:05:58 slusarz Exp $
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('ulaform', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    if ($e->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($e, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('ULAFORM_TEMPLATES', $registry->get('templates'));

/* Notification system. */
$notification = &Horde_Notification::singleton();
$notification->attach('status');

/* Find the base file path of Ulaform. */
if (!defined('ULAFORM_BASE')) {
    define('ULAFORM_BASE', dirname(__FILE__) . '/..');
}

/* Ulaform base libraries. */
require_once ULAFORM_BASE . '/lib/Ulaform.php';
require_once ULAFORM_BASE . '/lib/Driver.php';
require_once ULAFORM_BASE . '/lib/Action.php';

/* Templates */
require_once 'Horde/Template.php';
$template = new Horde_Template();

/* Forms libraries. */
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

/* Ulaform driver. */
$GLOBALS['ulaform_driver'] = &Ulaform_Driver::singleton('sql', $conf['sql']);
$GLOBALS['ulaform_driver']->initialise();

/* Start compression, if requested. */
Horde::compressOutput();
