<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @author Ben Klang <ben@alkaloid.net>
 * @package Vilma
 */

/* Check for a prior definition of HORDE_BASE (perhaps by an auto_prepend_file
 * definition for site customization). */
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

/* Load the Horde Framework core, and set up inclusion paths. */
require_once HORDE_BASE . '/lib/core.php';

/* Registry. */
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('vilma', !defined('AUTH_HANDLER'));
} catch (Horde_Exception $e) {
    if ($e->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($e, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
@define('VILMA_TEMPLATES', $registry->get('templates'));

/* Find the base file path of Vilma */
@define('VILMA_BASE', dirname(__FILE__) . '/..');

/* Vilma base library */
require_once VILMA_BASE . '/lib/Vilma.php';
require_once VILMA_BASE . '/lib/Driver.php';

/* Templates */
$template = &new Horde_Template();

/* Notification system. */
$notification = &Horde_Notification::singleton();
$notification->attach('status');

/* Vilma driver. */
$GLOBALS['vilma_driver'] = &Vilma_Driver::singleton();

// Get the currently active domain, possibly storing a change into the session
$curdomain = Vilma::getCurDomain();
