<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/base.load.php';
$imp_configured = (is_readable(IMP_BASE . '/config/conf.php') &&
                   is_readable(IMP_BASE . '/config/mime_drivers.php') &&
                   is_readable(IMP_BASE . '/config/prefs.php') &&
                   is_readable(IMP_BASE . '/config/servers.php'));

if (!$imp_configured) {
    require HORDE_BASE . '/lib/Test.php';
    Horde_Test::configFilesMissing('IMP', IMP_BASE,
        array('conf.php', 'mime_drivers.php', 'prefs.php'),
        array('servers.php' => 'This file controls the default settings for IMP, and also defines the list of available servers if you are using the server list.'));
}

// Will redirect to login page if not authenticated.
require_once IMP_BASE . '/lib/base.php';

// Load initial page as defined by view mode & preferences.
require IMP_Auth::getInitialPage();
