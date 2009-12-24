<?php
/**
 * $Horde: whups/index.php,v 1.30 2009/01/06 18:02:33 jan Exp $
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

define('WHUPS_BASE', dirname(__FILE__));
$whups_configured = (is_readable(WHUPS_BASE . '/config/conf.php') &&
                     is_readable(WHUPS_BASE . '/config/create_email.txt') &&
                     is_readable(WHUPS_BASE . '/config/mime_drivers.php') &&
                     is_readable(WHUPS_BASE . '/config/notify_email.txt') &&
                     is_readable(WHUPS_BASE . '/config/prefs.php') &&
                     is_readable(WHUPS_BASE . '/config/templates.php'));

if (!$whups_configured) {
    require WHUPS_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Whups', WHUPS_BASE,
        array('conf.php', 'mime_drivers.php', 'prefs.php'),
        array('templates.php' => 'This file defines the templates that various parts of Whups use to format data.',
              'create_email.txt' => 'This is the template for ticket creation emails',
              'notify_email.txt' => 'This is the template for ticket notification emails',
    ));
}

require_once WHUPS_BASE . '/lib/base.php';
require basename($prefs->getValue('whups_default_view') . '.php');
