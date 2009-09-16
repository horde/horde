<?php
/**
 * $Horde: beatnik/index.php,v 1.5 2008/08/22 08:53:50 duck Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

define('BEATNIK_BASE', dirname(__FILE__));
$beatnik_configured = (is_readable(BEATNIK_BASE . '/config/conf.php') &&
                       is_readable(BEATNIK_BASE . '/config/prefs.php'));

if (!$beatnik_configured) {
    require BEATNIK_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Beatnik', BEATNIK_BASE,
        array('conf.php', 'prefs.php'));
}

require BEATNIK_BASE . '/listzones.php';
