<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/lib/base.load.php';
$whups_configured = (is_readable(WHUPS_BASE . '/config/conf.php') &&
                     is_readable(WHUPS_BASE . '/config/create_email.txt') &&
                     is_readable(WHUPS_BASE . '/config/mime_drivers.php') &&
                     is_readable(WHUPS_BASE . '/config/notify_email.txt') &&
                     is_readable(WHUPS_BASE . '/config/prefs.php') &&
                     is_readable(WHUPS_BASE . '/config/templates.php'));

if (!$whups_configured) {
    require HORDE_BASE . '/lib/Test.php';
    Horde_Test::configFilesMissing('Whups', WHUPS_BASE,
        array('conf.php', 'mime_drivers.php', 'prefs.php'),
        array('templates.php' => 'This file defines the templates that various parts of Whups use to format data.',
              'create_email.txt' => 'This is the template for ticket creation emails',
              'notify_email.txt' => 'This is the template for ticket notification emails',
    ));
}

require_once WHUPS_BASE . '/lib/base.php';
require basename($prefs->getValue('whups_default_view') . '.php');
