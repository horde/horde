<?php
/**
 * This is the base include file for the Sesha application.
 *
 * @author Andrew Coleman <mercury@appisolutions.net>
 * @package Sesha
 */
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/../..');
}
// Set up base Horde includes
require_once HORDE_BASE . '/lib/core.php';

// Get a registry instance
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('sesha', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    if ($e->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($e, __FILE__, __LINE__, false);
}

// Configuration variables
$conf = &$GLOBALS['conf'];
@define('SESHA_TEMPLATES', $registry->get('templates'));
@define('SESHA_BASE', dirname(__FILE__) . '/..');

// Notification system
$notification = &Horde_Notification::singleton();
$notification->attach('status');

// Additional Horde libraries
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

// Sesha libraries
require_once SESHA_BASE . '/lib/Sesha.php';
require_once SESHA_BASE . '/lib/Driver.php';

// Start compression
Horde::compressOutput();

// Set the timezone
Horde_Nls::setTimeZone();

// Backend driver
$GLOBALS['backend'] = &Sesha_Driver::factory();
