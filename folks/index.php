<?php
/**
 * $Horde: folks/index.php,v 1.12 2008-01-02 11:14:00 jan Exp $
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Your Name <you@example.com>
 */

define('FOLKS_BASE', dirname(__FILE__));
$folks_configured = (is_readable(FOLKS_BASE . '/config/conf.php') &&
                        is_readable(FOLKS_BASE . '/config/prefs.php'));

if (!$folks_configured) {
    require FOLKS_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Folks', FOLKS_BASE,
                                   array('conf.php', 'prefs.php'));
}

require FOLKS_BASE . '/list.php';