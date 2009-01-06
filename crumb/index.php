<?php
/**
 * $Horde: crumb/index.php,v 1.12 2008/01/02 11:14:00 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

@define('CRUMB_BASE', dirname(__FILE__));
$crumb_configured = (is_readable(CRUMB_BASE . '/config/conf.php'));

if (!$crumb_configured) {
    require CRUMB_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Crumb', CRUMB_BASE,
                                   array('conf.php'));
}

require CRUMB_BASE . '/listclients.php';
