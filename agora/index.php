<?php
/**
 * $Horde: agora/index.php,v 1.13 2008/01/05 11:37:12 duck Exp $
 */

define('AGORA_BASE', dirname(__FILE__));
$agora_configured = (is_readable(AGORA_BASE . '/config/conf.php') &&
                     is_readable(AGORA_BASE . '/config/prefs.php'));

if (!$agora_configured) {
    require AGORA_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Agora', AGORA_BASE,
        array('conf.php', 'prefs.php'));
}

require AGORA_BASE . '/forums.php';
