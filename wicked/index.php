<?php
/**
 * $Horde: wicked/index.php,v 1.14 2009/01/06 18:02:39 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Tyler Colbert <tyler@colberts.us>
 */

@define('WICKED_BASE', dirname(__FILE__));
$wicked_configured = (is_readable(WICKED_BASE . '/config/conf.php') &&
                      is_readable(WICKED_BASE . '/config/prefs.php'));

if (!$wicked_configured) {
    require WICKED_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Wicked', WICKED_BASE,
                                   array('conf.php', 'prefs.php'));
}

require WICKED_BASE . '/display.php';
