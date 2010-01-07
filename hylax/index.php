<?php
/**
 * $Horde: incubator/hylax/index.php,v 1.12 2009/01/06 17:50:48 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('HYLAX_BASE', dirname(__FILE__));
$hylax_configured = (is_readable(HYLAX_BASE . '/config/conf.php') &&
                     is_readable(HYLAX_BASE . '/config/prefs.php') &&
                     is_readable(HYLAX_BASE . '/config/covers.php'));

if (!$hylax_configured) {
    require HYLAX_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Hylax', HYLAX_BASE,
        array('conf.php', 'prefs.php'),
        array('covers.php' => 'This file defines the templates for Cover Sheets.'));
}

require HYLAX_BASE . '/folder.php';
