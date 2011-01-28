<?php
/**
 * Hylax Application class file.
 *
 * This file brings in all of the dependencies that every Hylax script will
 * need, and sets up objects that all scripts use.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Hylax
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

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Hylax_Application extends Horde_Registry_Application
{
    public $version = 'H4 (0.1-git)';

    public $gateway = null;
    public $storage = null;

    protected function _init()
    {
        /* Hylax Driver */
        $this->gateway = Hylax_Driver::singleton($conf['fax']['driver'],
                                                 $conf['fax']['params']);

        /* Hylax storage driver. */
        $this->storage = Hylax_Storage::singleton('sql', $conf['sql']);
    }

}
