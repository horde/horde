<?php
/**
 * This is an inventory application written for the Horde framework.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

@define('SESHA_BASE', dirname(__FILE__));
$sesha_configured = (is_readable(SESHA_BASE . '/config/conf.php') &&
                     is_readable(SESHA_BASE . '/config/prefs.php'));

if (!$sesha_configured) {
    require SESHA_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Sesha', SESHA_BASE,
        array('conf.php', 'prefs.php'));
}

require SESHA_BASE . '/list.php';
