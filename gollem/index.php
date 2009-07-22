<?php
/**
 * Gollem index file.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Max Kalika <max@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Gollem
 */

require_once dirname(__FILE__) . '/lib/base.load.php';
$gollem_configured = (is_readable(GOLLEM_BASE . '/config/backends.php') &&
                      is_readable(GOLLEM_BASE . '/config/conf.php') &&
                      is_readable(GOLLEM_BASE . '/config/credentials.php') &&
                      is_readable(GOLLEM_BASE . '/config/mime_drivers.php') &&
                      is_readable(GOLLEM_BASE . '/config/prefs.php'));

if (!$gollem_configured) {
    require HORDE_BASE . '/lib/Test.php';
    Horde_Test::configFilesMissing('Gollem', GOLLEM_BASE,
        array('conf.php', 'mime_drivers.php', 'prefs.php', 'backends.php'),
        array('credentials.php' => 'This file defines types of credentials that a backend might request.'));
}

require GOLLEM_BASE . '/manager.php';
