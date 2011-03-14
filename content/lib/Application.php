<?php
/**
 * Content application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Content through this API.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Content
 */

/* Determine the base directories. */
if (!defined('CONTENT_BASE')) {
    define('CONTENT_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(CONTENT_BASE . '/config/horde.local.php')) {
        include CONTENT_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', CONTENT_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Content_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H4 (1.0-git)';
}
