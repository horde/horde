<?php
/**
 * Pastie application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Pastie through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Pastie
 */

/* Determine the base directories. */
if (!defined('PASTIE_BASE')) {
    define('PASTIE_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(PASTIE_BASE . '/config/horde.local.php')) {
        include PASTIE_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', PASTIE_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Pastie_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H4 (0.1-git)';

    /**
     */
    protected function _init()
    {
        try {
            $this->driver = Pastie_Driver::factory();
        } catch (Pastie_Exception $e) {
            $GLOBALS['notification']->notify($e);
        }
    }

    /**
     */
    public function menu($menu)
    {
        return Pastie::getMenu();
    }

}
