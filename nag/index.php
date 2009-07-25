<?php
/**
 * Nag index script.
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/base.load.php';
$nag_configured = (is_readable(NAG_BASE . '/config/conf.php') &&
                   is_readable(NAG_BASE . '/config/prefs.php'));

if (!$nag_configured) {
    require HORDE_BASE . '/lib/Test.php';
    Horde_Test::configFilesMissing('Nag', NAG_BASE,
        array('conf.php', 'prefs.php'));
}

require NAG_BASE . '/list.php';
