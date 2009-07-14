<?php
/**
 * Script to determine the correct *_BASE values.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Turba
 */

$curr_dir = dirname(__FILE__);

if (!defined('HORDE_BASE')) {
    /* Temporary fix - if horde does not live directly under the app
     * directory, the HORDE_BASE constant should be defined in
     * lib/base.local.php. */
    if (file_exists($curr_dir . '/base.local.php')) {
        include $curr_dir . '/base.local.php';
    } else {
        define('HORDE_BASE', $curr_dir . '/../..');
    }
}

if (!defined('TURBA_BASE')) {
    define('TURBA_BASE', $curr_dir . '/..');
}
