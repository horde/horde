<?php
/**
 * $Horde: skoli/index.php,v 0.1 $
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('SKOLI_BASE', dirname(__FILE__));
$skoli_configured = (is_readable(SKOLI_BASE . '/config/conf.php') &&
                     is_readable(SKOLI_BASE . '/config/prefs.php') &&
                     is_readable(SKOLI_BASE . '/config/schools.php'));

if (!$skoli_configured) {
    require SKOLI_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Skoli', SKOLI_BASE,
                                   array('conf.php', 'prefs.php'),
                                   array('schools.php' => _('This file defines templates for new classes.')));
}

require_once SKOLI_BASE . '/lib/base.php';
require SKOLI_BASE . '/' . $prefs->getValue('initial_page') . '.php';
