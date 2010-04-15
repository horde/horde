<?php
/**
 * Mnemo application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Mnemo through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Mnemo
 */

/* Determine the base directories. */
if (!defined('MNEMO_BASE')) {
    define('MNEMO_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(MNEMO_BASE . '/config/horde.local.php')) {
        include MNEMO_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', MNEMO_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Mnemo_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     *   $mnemo_shares - TODO
     */
    protected function _init()
    {
	// Set the timezone variable.
	Horde_Nls::setTimeZone();

	// Create a share instance.
	$GLOBALS['mnemo_shares'] = Horde_Share::singleton($GLOBALS['registry']->getApp());

	Mnemo::initialize();
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        global $conf, $prefs, $registry;

        switch ($ui->group) {
        case 'share':
            if (!$prefs->isLocked('default_notepad')) {
                $notepads = array();
                foreach (Mnemo::listNotepads() as $key => $val) {
                    $notepads[htmlspecialchars($key)] = htmlspecialchars($val->get('name'));
                }
                $ui->override['default_notepad'] = $notepads;
            }
            break;
        }
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Mnemo::getMenu();
    }

}

// Mnemo libraries.
require_once MNEMO_BASE . '/lib/Mnemo.php';
require_once MNEMO_BASE . '/lib/Driver.php';

// Start compression, if requested.
Horde::compressOutput();
