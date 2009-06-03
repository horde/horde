<?php
/**
 * Index script.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

@define('INGO_BASE', dirname(__FILE__));
$ingo_configured = (is_readable(INGO_BASE . '/config/conf.php') &&
                    is_readable(INGO_BASE . '/config/prefs.php') &&
                    is_readable(INGO_BASE . '/config/backends.php') &&
                    is_readable(INGO_BASE . '/config/fields.php'));

if (!$ingo_configured) {
    if (!defined('HORDE_BASE')) {
        /* Temporary fix - if horde does not live directly under the imp
         * directory, the HORDE_BASE constant should be defined in
         * imp/lib/base.local.php. */
        if (file_exists(INGO_BASE . '/lib/base.local.php')) {
            include INGO_BASE . '/lib/base.local.php';
        } else {
            define('HORDE_BASE', INGO_BASE . '/..');
        }
    }

    require HORDE_BASE . '/lib/Test.php';
    Horde_Test::configFilesMissing('Ingo', INGO_BASE,
        array('conf.php', 'prefs.php', 'backends.php'),
        array('fields.php' => 'This file defines types of credentials that a backend might request.'));
}

require INGO_BASE . '/filters.php';
