<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

if (!defined('PASSWD_BASE')) {
    define('PASSWD_BASE', __DIR__. '/..');
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

/**
 * Passwd application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with IMP through this API.
 *
 * @author    Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @author    Ben Klang <ben@alkaloid.net>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Application extends Horde_Registry_Application
{
    /**
     * The version of passwd as shown in the admin view
     */
    public $version = 'H5 (5.0.3)';
}
