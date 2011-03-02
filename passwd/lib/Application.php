<?php
/**
 * Passwd application interface.
 *
 * This file brings in all of the dependencies that every PASSWD script will
 * need, and sets up objects that all scripts use.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Passwd
 */

if (!defined('PASSWD_BASE')) {
    define('PASSWD_BASE', dirname(__FILE__). '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(PASSWD_BASE. '/config/horde.local.php')) {
        include PASSWD_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', PASSWD_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Passwd_Application extends Horde_Registry_Application {
    /**
     * The version of passwd as shown in the admin view
     */
    public $version = 'H4 (3.1.4-git)';




}
