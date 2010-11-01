<?php

/* Determine the base directories. */
if (!defined('TIMEOBJECTS_BASE')) {
    define('TIMEOBJECTS_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(TIMEOBJECTS_BASE . '/config/horde.local.php')) {
        include TIMEOBJECTS_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', TIMEOBJECTS_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 *  Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Timeobjects_Application extends Horde_Registry_Application
{
    public $version = 'H4 (0.1-git)';
}
