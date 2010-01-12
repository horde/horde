<?php
/**
 * Script to determine the correct *_BASE values.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Turba
 */

if (!defined('TURBA_BASE')) {
    define('TURBA_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(TURBA_BASE . '/config/horde.local.php')) {
        include TURBA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', TURBA_BASE . '/..');
    }
}
