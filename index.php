<?php
/**
 * $Horde: shout/index.php,v 0.1 2005/06/28 10:35:46 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

define('SHOUT_BASE', dirname(__FILE__));
$shout_configured = (@is_readable(SHOUT_BASE . '/config/conf.php') &&
                     @is_readable(SHOUT_BASE . '/config/prefs.php'));

// if (!$shout_configured) {
//     require SHOUT_BASE . '/../lib/Test.php';
//     Horde_Test::configFilesMissing('Shout', SHOUT_BASE,
//                                    array('conf.php', 'prefs.php'));
// }

require SHOUT_BASE . '/contexts.php';