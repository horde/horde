<?php
/**
 * Hermes application interface.
 *
 * This file is responsible for initializing the Hermes application.
 *
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2010 Alkaloid Networks (http://projects.alkaloid.net/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Hermes
 */

if (!defined('HERMES_BASE')) {
    define('HERMES_BASE', dirname(__FILE__). '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(HERMES_BASE. '/config/horde.local.php')) {
        include HERMES_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', HERMES_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Hermes_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Driver object for reading/writing time entries
     */
    static public $driver = null;

    /**
     * TODO
     */
    static protected $_perms = array();

    /**
     * Initialization function.
     *
     * Global variables defined:
     */
    protected function _init()
    {
        try {
            $this->driver = Hermes::getDriver();
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e);
            return false;
        }
    }

    /**
     * Interface to define settable permissions within Horde
     */
    public function perms()
    {
        if (!empty(self::$_perms)) {
            return self::$_perms;
        }

        self::$_perms = array();
        self::$_perms['tree']['hermes']['review'] = array();
        self::$_perms['title']['hermes:review'] = _("Time Review Screen");
        self::$_perms['tree']['hermes']['deliverables'] = array();
        self::$_perms['title']['hermes:deliverables'] = _("Deliverables");
        self::$_perms['tree']['hermes']['invoicing'] = array();
        self::$_perms['title']['hermes:invoicing'] = _("Invoicing");

        return self::$_perms;
    }

}
